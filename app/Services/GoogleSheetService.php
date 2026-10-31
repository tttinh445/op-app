<?php

namespace App\Services;

use App\Contracts\GoogleSheetSyncable;
use App\Models\Customer;
use App\Models\GoogleSheetConfig;
use App\Models\PendingSheetSync;
use App\Models\Product;
use App\Models\Supplier;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    /**
     * Flag to prevent recursive syncing when importing from sheet.
     */
    public static bool $isSyncingFromSheet = false;

    /**
     * Fetch raw rows from a Google Sheet according to its configuration.
     *
     * @return array<int, array<int, string>>
     */
    public function fetchSheetRows(GoogleSheetConfig $config): array
    {
        if ($config->sync_mode === 'api_key' && $config->api_key) {
            return $this->fetchViaApiKey($config);
        }

        if ($config->sync_mode === 'apps_script' && $config->apps_script_url) {
            return $this->fetchViaAppsScript($config);
        }

        // Default: fetch via public CSV export
        return $this->fetchViaPublicCsv($config);
    }

    /**
     * Fetch rows via public CSV endpoint.
     *
     * @return array<int, array<int, string>>
     */
    protected function fetchViaPublicCsv(GoogleSheetConfig $config): array
    {
        $url = $config->getPublicCsvUrl();
        $response = Http::timeout(15)->get($url);

        if (! $response->successful()) {
            throw new Exception("Không thể kết nối đến Google Sheet (Mã lỗi: {$response->status()}). Hãy đảm bảo Google Sheet đã bật chế độ 'Bất kỳ ai có liên kết đều có thể xem' (Public viewer).");
        }

        $csvContent = $response->body();

        return $this->parseCsvString($csvContent);
    }

    /**
     * Fetch rows using Google Sheets API v4.
     *
     * @return array<int, array<int, string>>
     */
    protected function fetchViaApiKey(GoogleSheetConfig $config): array
    {
        $sheetName = urlencode($config->sheet_name ?: 'Sheet1');
        $range = $config->range ?: 'A1:Z';
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$config->spreadsheet_id}/values/{$sheetName}!{$range}?key={$config->api_key}";

        $response = Http::timeout(15)->get($url);

        if (! $response->successful()) {
            $msg = $response->json('error.message') ?? "Lỗi API Google ({$response->status()})";
            throw new Exception("Lỗi khi gọi Google Sheets API: {$msg}");
        }

        /** @var array<int, array<int, string>> $values */
        $values = $response->json('values') ?? [];

        return $values;
    }

    /**
     * Fetch rows via Google Apps Script Web App.
     *
     * @return array<int, array<int, string>>
     */
    protected function fetchViaAppsScript(GoogleSheetConfig $config): array
    {
        $response = Http::timeout(20)
            ->withOptions(['allow_redirects' => true])
            ->get($config->apps_script_url, [
                'action' => 'read',
                'sheet' => $config->sheet_name,
            ]);

        if (! $response->successful()) {
            throw new Exception("Không thể kết nối tới Google Apps Script Webhook ({$response->status()})");
        }

        $data = $response->json();
        if (is_array($data) && isset($data['rows'])) {
            return $data['rows'];
        }

        if (is_array($data)) {
            return $data;
        }

        throw new Exception('Dữ liệu trả về từ Apps Script không hợp lệ');
    }

    /**
     * Parse raw CSV string to rows array.
     *
     * @return array<int, array<int, string>>
     */
    public function parseCsvString(string $csvContent): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $csvContent));
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $row = str_getcsv($line);
            $rows[] = array_map(fn ($cell) => trim((string) $cell), $row);
        }

        return $rows;
    }

    /**
     * Synchronize data from Google Sheet into local database models.
     *
     * @return array{success: bool, imported: int, updated: int, total: int, message: string}
     */
    public function syncFromSheet(GoogleSheetConfig $config): array
    {
        self::$isSyncingFromSheet = true;

        try {
            $rows = $this->fetchSheetRows($config);

            if (empty($rows)) {
                return [
                    'success' => false,
                    'imported' => 0,
                    'updated' => 0,
                    'total' => 0,
                    'message' => 'Không có dữ liệu trong sheet này.',
                ];
            }

            // Determine if first row is header
            $firstRow = $rows[0];
            $isHeader = $this->isHeaderRow($firstRow, $config->sheet_type);
            $dataRows = $isHeader ? array_slice($rows, 1) : $rows;

            $imported = 0;
            $updated = 0;

            foreach ($dataRows as $row) {
                if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                    continue;
                }

                try {
                    if ($config->sheet_type === 'customer') {
                        $attributes = Customer::fromSheetRow($row);
                        if (empty($attributes['code']) || empty($attributes['name']) || empty($attributes['tax_id'])) {
                            continue;
                        }

                        $customer = Customer::where('code', $attributes['code'])
                            ->orWhere('tax_id', $attributes['tax_id'])
                            ->first();

                        if ($customer) {
                            $customer->update(array_merge($attributes, [
                                'google_sheet_config_id' => $config->id,
                                'synced_at' => now(),
                                'sync_status' => 'synced',
                            ]));
                            $updated++;
                        } else {
                            Customer::create(array_merge($attributes, [
                                'google_sheet_config_id' => $config->id,
                                'synced_at' => now(),
                                'sync_status' => 'synced',
                            ]));
                            $imported++;
                        }
                        PendingSheetSync::where('sheet_type', 'customer')
                            ->where('code', $attributes['code'])
                            ->update(['status' => 'synced']);
                    } elseif ($config->sheet_type === 'supplier') {
                        $attributes = Supplier::fromSheetRow($row);
                        if (empty($attributes['code']) || empty($attributes['name']) || empty($attributes['tax_id'])) {
                            continue;
                        }

                        $supplier = Supplier::where('code', $attributes['code'])
                            ->orWhere('tax_id', $attributes['tax_id'])
                            ->first();

                        if ($supplier) {
                            $supplier->update(array_merge($attributes, [
                                'google_sheet_config_id' => $config->id,
                                'synced_at' => now(),
                                'sync_status' => 'synced',
                            ]));
                            $updated++;
                        } else {
                            Supplier::create(array_merge($attributes, [
                                'google_sheet_config_id' => $config->id,
                                'synced_at' => now(),
                                'sync_status' => 'synced',
                            ]));
                            $imported++;
                        }
                        PendingSheetSync::where('sheet_type', 'supplier')
                            ->where('code', $attributes['code'])
                            ->update(['status' => 'synced']);
                    } elseif ($config->sheet_type === 'product') {
                        $attributes = Product::fromSheetRow($row);
                        if (empty($attributes['code']) || empty($attributes['name'])) {
                            continue;
                        }

                        $product = Product::where('code', $attributes['code'])->first();
                        if ($product) {
                            $product->update(array_merge($attributes, [
                                'google_sheet_config_id' => $config->id,
                                'synced_at' => now(),
                                'sync_status' => 'synced',
                            ]));
                            $updated++;
                        } else {
                            Product::create(array_merge($attributes, [
                                'google_sheet_config_id' => $config->id,
                                'synced_at' => now(),
                                'sync_status' => 'synced',
                            ]));
                            $imported++;
                        }
                        PendingSheetSync::where('sheet_type', 'product')
                            ->where('code', $attributes['code'])
                            ->update(['status' => 'synced']);
                    }
                } catch (Exception $e) {
                    Log::warning('Error syncing row: '.$e->getMessage(), ['row' => $row]);
                }
            }

            $config->update(['last_synced_at' => now()]);

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'total' => $imported + $updated,
                'message' => "Đồng bộ thành công! Thêm mới: {$imported}, Cập nhật: {$updated}.",
            ];
        } finally {
            self::$isSyncingFromSheet = false;
        }
    }

    /**
     * Synchronize a model addition or update to linked Google Sheets.
     *
     * @param  string  $action  'append' | 'update'
     * @return array{synced: bool, message: string}
     */
    public function syncModelToSheet(GoogleSheetSyncable $model, string $action = 'append', ?string $originalCode = null): array
    {
        if (self::$isSyncingFromSheet) {
            return ['synced' => false, 'message' => 'Đang trong quá trình nhập từ Google Sheet.'];
        }

        $sheetType = $model::sheetType();
        $code = (string) ($model->code ?? '');

        // Find relevant active Google Sheet configurations
        $configsQuery = GoogleSheetConfig::where('sheet_type', $sheetType)->where('is_active', true);
        if ($model instanceof Model && ! empty($model->google_sheet_config_id)) {
            $configsQuery->where(function ($q) use ($model) {
                $q->where('id', $model->google_sheet_config_id)->orWhere('sheet_type', $model::sheetType());
            });
        }

        $configs = $configsQuery->get();

        if ($configs->isEmpty()) {
            return [
                'synced' => false,
                'message' => 'Chưa có cấu hình Google Sheet nào cho danh mục này.',
            ];
        }

        $successCount = 0;
        $messages = [];
        $row = $model->toSheetRow();

        foreach ($configs as $config) {
            if (empty($config->apps_script_url)) {
                $messages[] = "Sheet \"{$config->name}\" chưa cài đặt Webhook URL (Apps Script).";

                continue;
            }

            try {
                $payload = [
                    'action' => $action,
                    'sheet' => $config->sheet_name,
                    'row' => $row,
                    'key' => $code,
                    'originalKey' => $originalCode ?? $code,
                    'keyColumn' => $config->getKeyColumnLetter(),
                    'keyColumnIndex' => $config->getKeyColumnIndex(),
                ];

                $response = Http::timeout(15)
                    ->withOptions(['allow_redirects' => true])
                    ->post($config->apps_script_url, $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json) && ($json['success'] ?? false)) {
                        $successCount++;
                        $config->update(['last_synced_at' => now()]);

                        if ($model instanceof Model) {
                            $model->updateQuietly([
                                'google_sheet_config_id' => $config->id,
                                'synced_at' => now(),
                                'sync_status' => 'synced',
                            ]);
                        }
                    } else {
                        $err = $json['message'] ?? 'Phản hồi không thành công từ Apps Script.';
                        if (str_contains($err, 'Hành động không hợp lệ') && $action === 'update') {
                            // Fallback to append on legacy script
                            $fallbackRes = Http::timeout(15)
                                ->withOptions(['allow_redirects' => true])
                                ->post($config->apps_script_url, [
                                    'action' => 'append',
                                    'sheet' => $config->sheet_name,
                                    'row' => $row,
                                ]);
                            if ($fallbackRes->successful() && ($fallbackRes->json('success') ?? false)) {
                                $successCount++;
                                $config->update(['last_synced_at' => now()]);
                                if ($model instanceof Model) {
                                    $model->updateQuietly([
                                        'google_sheet_config_id' => $config->id,
                                        'synced_at' => now(),
                                        'sync_status' => 'synced',
                                    ]);
                                }
                                $messages[] = 'Đã lưu dòng mới vào Google Sheet (chế độ tương thích)';

                                continue;
                            }
                        }
                        $messages[] = $err;
                    }
                } else {
                    $messages[] = "Lỗi kết nối Webhook ({$response->status()})";
                }
            } catch (Exception $e) {
                Log::warning("Lỗi đồng bộ {$action} lên Google Sheet: ".$e->getMessage());
                $messages[] = $e->getMessage();
            }
        }

        if ($successCount > 0) {
            return [
                'synced' => true,
                'message' => 'Đã cập nhật đồng thời lên Google Sheet.',
            ];
        }

        return [
            'synced' => false,
            'message' => ! empty($messages) ? implode('; ', $messages) : 'Không thể kết nối đến Google Sheet.',
        ];
    }

    /**
     * Synchronize a model deletion to linked Google Sheets.
     *
     * @param  string  $sheetType  'customer' | 'supplier' | 'product'
     * @param  string  $code  Model's unique code (column 0)
     * @return array{synced: bool, message: string}
     */
    public function syncDeleteToSheet(string $sheetType, string $code, ?int $configId = null): array
    {
        if (self::$isSyncingFromSheet) {
            return ['synced' => false, 'message' => 'Đang trong quá trình nhập từ Google Sheet.'];
        }

        $configsQuery = GoogleSheetConfig::where('sheet_type', $sheetType)->where('is_active', true);
        if ($configId) {
            $configsQuery->where(function ($q) use ($configId, $sheetType) {
                $q->where('id', $configId)->orWhere('sheet_type', $sheetType);
            });
        }

        $configs = $configsQuery->get();

        if ($configs->isEmpty()) {
            return [
                'synced' => false,
                'message' => 'Chưa có cấu hình Google Sheet cho mục này.',
            ];
        }

        $successCount = 0;
        $messages = [];

        foreach ($configs as $config) {
            if (empty($config->apps_script_url)) {
                continue;
            }

            try {
                $payload = [
                    'action' => 'delete',
                    'sheet' => $config->sheet_name,
                    'key' => $code,
                    'keyColumn' => $config->getKeyColumnLetter(),
                    'keyColumnIndex' => $config->getKeyColumnIndex(),
                ];

                $response = Http::timeout(15)
                    ->withOptions(['allow_redirects' => true])
                    ->post($config->apps_script_url, $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json) && ($json['success'] ?? false)) {
                        $successCount++;
                        $config->update(['last_synced_at' => now()]);
                        PendingSheetSync::where('sheet_type', $sheetType)
                            ->where('code', $code)
                            ->where('action', 'delete')
                            ->update(['status' => 'synced']);
                    } else {
                        $err = is_array($json) ? ($json['message'] ?? 'Không xoá được trên Google Sheet.') : 'Lỗi kết nối';
                        if (str_contains($err, 'Hành động không hợp lệ') || str_contains($err, 'Hành động không xác định')) {
                            $messages[] = 'Webhook chưa hỗ trợ xoá (vui lòng nâng cấp Apps Script v2.1 và chọn Phiên bản mới)';
                        } else {
                            $messages[] = $err;
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning('Lỗi xoá dòng trên Google Sheet: '.$e->getMessage());
                $messages[] = $e->getMessage();
            }
        }

        if ($successCount > 0) {
            return [
                'synced' => true,
                'message' => 'Đã xoá dòng tương ứng trên Google Sheet.',
            ];
        }

        return [
            'synced' => false,
            'message' => ! empty($messages) ? implode('; ', $messages) : 'Chưa xoá được trên Google Sheet.',
        ];
    }

    /**
     * Push all local records of a specific type to Google Sheet.
     *
     * @return array{success: bool, total: int, message: string}
     */
    public function pushAllToSheet(GoogleSheetConfig $config): array
    {
        if (empty($config->apps_script_url)) {
            throw new Exception("Cấu hình \"{$config->name}\" chưa được cài đặt URL Webhook (Apps Script). Hãy dán URL Webhook để kích hoạt đồng bộ 2 chiều.");
        }

        $headers = [];
        $rows = [];

        if ($config->sheet_type === 'customer') {
            $headers = Customer::sheetHeaders();
            $rows = Customer::all()->map(fn ($c) => $c->toSheetRow())->all();
        } elseif ($config->sheet_type === 'supplier') {
            $headers = Supplier::sheetHeaders();
            $rows = Supplier::all()->map(fn ($s) => $s->toSheetRow())->all();
        } elseif ($config->sheet_type === 'product') {
            $headers = Product::sheetHeaders();
            $rows = Product::all()->map(fn ($p) => $p->toSheetRow())->all();
        }

        $payload = [
            'action' => 'sync_all',
            'sheet' => $config->sheet_name,
            'headers' => $headers,
            'rows' => $rows,
        ];

        $response = Http::timeout(30)
            ->withOptions(['allow_redirects' => true])
            ->post($config->apps_script_url, $payload);

        if (! $response->successful()) {
            throw new Exception("Lỗi khi kết nối tới Webhook ({$response->status()})");
        }

        $json = $response->json();
        if (is_array($json) && ! ($json['success'] ?? false)) {
            $errMessage = $json['message'] ?? '';
            if (str_contains($errMessage, 'Hành động không hợp lệ')) {
                return $this->fallbackAppendRows($config, $rows);
            }

            throw new Exception($errMessage ?: 'Không thể ghi dữ liệu lên Google Sheet');
        }

        $count = count($rows);
        $config->update(['last_synced_at' => now()]);

        // Mark all records of this sheet type as synced
        if ($config->sheet_type === 'customer') {
            Customer::query()->update(['sync_status' => 'synced', 'synced_at' => now()]);
        } elseif ($config->sheet_type === 'supplier') {
            Supplier::query()->update(['sync_status' => 'synced', 'synced_at' => now()]);
        } elseif ($config->sheet_type === 'product') {
            Product::query()->update(['sync_status' => 'synced', 'synced_at' => now()]);
        }
        PendingSheetSync::where('sheet_type', $config->sheet_type)->update(['status' => 'synced']);

        return [
            'success' => true,
            'total' => $count,
            'message' => "Đã đẩy toàn bộ {$count} dòng dữ liệu lên Google Sheet thành công!",
        ];
    }

    /**
     * Fallback to appending rows one-by-one for legacy Apps Script that only supports 'append'.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{success: bool, total: int, message: string}
     */
    protected function fallbackAppendRows(GoogleSheetConfig $config, array $rows): array
    {
        $count = 0;
        foreach ($rows as $row) {
            try {
                $response = Http::timeout(15)
                    ->withOptions(['allow_redirects' => true])
                    ->post($config->apps_script_url, [
                        'action' => 'append',
                        'sheet' => $config->sheet_name,
                        'row' => $row,
                    ]);

                if ($response->successful() && ($response->json('success') ?? false)) {
                    $count++;
                }
            } catch (Exception $e) {
                Log::warning('Fallback append error: '.$e->getMessage());
            }
        }

        $config->update(['last_synced_at' => now()]);

        if ($config->sheet_type === 'customer') {
            Customer::query()->update(['sync_status' => 'synced', 'synced_at' => now()]);
        } elseif ($config->sheet_type === 'supplier') {
            Supplier::query()->update(['sync_status' => 'synced', 'synced_at' => now()]);
        } elseif ($config->sheet_type === 'product') {
            Product::query()->update(['sync_status' => 'synced', 'synced_at' => now()]);
        }
        PendingSheetSync::where('sheet_type', $config->sheet_type)->update(['status' => 'synced']);

        return [
            'success' => true,
            'total' => $count,
            'message' => "Đã đẩy thành công {$count} dòng lên Google Sheet (chế độ tương thích). Vui lòng cập nhật Apps Script v2.1 trên Google Sheet để tăng tốc và hỗ trợ sửa/xoá trực tiếp.",
        ];
    }

    /**
     * Record a pending deletion to be pushed to Google Sheet.
     */
    public function recordPendingDelete(string $sheetType, string $code, ?string $name = null, ?int $configId = null): PendingSheetSync
    {
        return PendingSheetSync::updateOrCreate(
            [
                'sheet_type' => $sheetType,
                'code' => $code,
                'action' => 'delete',
            ],
            [
                'name' => $name,
                'google_sheet_config_id' => $configId,
                'status' => 'pending',
                'error_message' => null,
            ]
        );
    }

    /**
     * Summary of changes waiting to be pushed to Google Sheet.
     *
     * @return array{total: int, creates: int, updates: int, deletes: int, pending_deletes: Collection<int, PendingSheetSync>}
     */
    public function getPendingChangesSummary(string $sheetType): array
    {
        $creates = match ($sheetType) {
            'customer' => Customer::where('sync_status', 'pending_create')->count(),
            'supplier' => Supplier::where('sync_status', 'pending_create')->count(),
            'product' => Product::where('sync_status', 'pending_create')->count(),
            default => 0,
        };

        $updates = match ($sheetType) {
            'customer' => Customer::where('sync_status', 'pending_update')->count(),
            'supplier' => Supplier::where('sync_status', 'pending_update')->count(),
            'product' => Product::where('sync_status', 'pending_update')->count(),
            default => 0,
        };

        /** @var Collection<int, PendingSheetSync> $pendingDeletes */
        $pendingDeletes = PendingSheetSync::where('sheet_type', $sheetType)
            ->where('action', 'delete')
            ->where('status', 'pending')
            ->get();

        $deletes = $pendingDeletes->count();

        return [
            'total' => $creates + $updates + $deletes,
            'creates' => $creates,
            'updates' => $updates,
            'deletes' => $deletes,
            'pending_deletes' => $pendingDeletes,
        ];
    }

    /**
     * Push ONLY marked changes (new, updated, deleted) to Google Sheet.
     *
     * @return array{success: bool, total: int, creates: int, updates: int, deletes: int, message: string}
     */
    public function pushMarkedChanges(GoogleSheetConfig $config): array
    {
        if (empty($config->apps_script_url)) {
            throw new Exception("Cấu hình \"{$config->name}\" chưa được cài đặt URL Webhook (Apps Script). Hãy dán URL Webhook để kích hoạt đồng bộ 2 chiều.");
        }

        $sheetType = $config->sheet_type;

        $pendingCreates = match ($sheetType) {
            'customer' => Customer::where('sync_status', 'pending_create')->get(),
            'supplier' => Supplier::where('sync_status', 'pending_create')->get(),
            'product' => Product::where('sync_status', 'pending_create')->get(),
            default => collect(),
        };

        $pendingUpdates = match ($sheetType) {
            'customer' => Customer::where('sync_status', 'pending_update')->get(),
            'supplier' => Supplier::where('sync_status', 'pending_update')->get(),
            'product' => Product::where('sync_status', 'pending_update')->get(),
            default => collect(),
        };

        $pendingDeletes = PendingSheetSync::where('sheet_type', $sheetType)
            ->where('action', 'delete')
            ->where('status', 'pending')
            ->get();

        $createsCount = $pendingCreates->count();
        $updatesCount = $pendingUpdates->count();
        $deletesCount = $pendingDeletes->count();
        $total = $createsCount + $updatesCount + $deletesCount;

        if ($total === 0) {
            return [
                'success' => true,
                'total' => 0,
                'creates' => 0,
                'updates' => 0,
                'deletes' => 0,
                'message' => 'Không có dữ liệu thay đổi nào cần đẩy lên Google Sheet.',
            ];
        }

        $createsRows = $pendingCreates->map(fn ($m) => $m->toSheetRow())->all();
        $updatesPayload = $pendingUpdates->map(fn ($m) => [
            'key' => $m->code,
            'originalKey' => $m->code,
            'row' => $m->toSheetRow(),
        ])->all();
        $deletesKeys = $pendingDeletes->pluck('code')->all();

        $payload = [
            'action' => 'sync_changes',
            'sheet' => $config->sheet_name,
            'keyColumn' => $config->getKeyColumnLetter(),
            'keyColumnIndex' => $config->getKeyColumnIndex(),
            'creates' => $createsRows,
            'updates' => $updatesPayload,
            'deletes' => $deletesKeys,
        ];

        $response = Http::timeout(30)
            ->withOptions(['allow_redirects' => true])
            ->post($config->apps_script_url, $payload);

        if (! $response->successful()) {
            throw new Exception("Lỗi khi kết nối tới Webhook ({$response->status()})");
        }

        $json = $response->json();
        $isInvalidAction = false;
        if (is_array($json) && ! ($json['success'] ?? false)) {
            $errMessage = (string) ($json['message'] ?? '');
            if (str_contains($errMessage, 'Hành động không hợp lệ') || str_contains($errMessage, 'Hành động không xác định')) {
                $isInvalidAction = true;
            } else {
                throw new Exception($errMessage ?: 'Không thể đẩy thay đổi lên Google Sheet.');
            }
        }

        if ($isInvalidAction) {
            return $this->fallbackPushMarkedChanges($config, $pendingCreates, $pendingUpdates, $pendingDeletes);
        }

        // Mark models as synced in local DB
        foreach ($pendingCreates as $item) {
            $item->updateQuietly([
                'sync_status' => 'synced',
                'synced_at' => now(),
                'google_sheet_config_id' => $config->id,
            ]);
        }

        foreach ($pendingUpdates as $item) {
            $item->updateQuietly([
                'sync_status' => 'synced',
                'synced_at' => now(),
                'google_sheet_config_id' => $config->id,
            ]);
        }

        foreach ($pendingDeletes as $deleteItem) {
            $deleteItem->update(['status' => 'synced']);
        }

        $config->update(['last_synced_at' => now()]);

        return [
            'success' => true,
            'total' => $total,
            'creates' => $createsCount,
            'updates' => $updatesCount,
            'deletes' => $deletesCount,
            'message' => "Đã đẩy thành công {$total} thay đổi lên Google Sheet! (Thêm mới: {$createsCount}, Cập nhật: {$updatesCount}, Xoá: {$deletesCount}).",
        ];
    }

    /**
     * Fallback for pushing marked changes one-by-one on legacy Apps Script deployments.
     *
     * @param  Collection<int, Model>  $creates
     * @param  Collection<int, Model>  $updates
     * @param  Collection<int, PendingSheetSync>  $deletes
     * @return array{success: bool, total: int, creates: int, updates: int, deletes: int, message: string}
     */
    protected function fallbackPushMarkedChanges(
        GoogleSheetConfig $config,
        Collection $creates,
        Collection $updates,
        Collection $deletes
    ): array {
        $syncedCreates = 0;
        $syncedUpdates = 0;
        $syncedDeletes = 0;

        $deleteErrors = [];
        $updateErrors = [];
        $createErrors = [];

        $keyColumnIndex = $config->getKeyColumnIndex();
        $keyColumnLetter = $config->getKeyColumnLetter();

        // 1. Process deletes
        foreach ($deletes as $del) {
            try {
                $res = Http::timeout(10)->withOptions(['allow_redirects' => true])
                    ->post($config->apps_script_url, [
                        'action' => 'delete',
                        'sheet' => $config->sheet_name,
                        'key' => $del->code,
                        'keyColumn' => $keyColumnLetter,
                        'keyColumnIndex' => $keyColumnIndex,
                    ]);
                $json = $res->json();
                if ($res->successful() && is_array($json) && ($json['success'] ?? false)) {
                    $del->update(['status' => 'synced']);
                    $syncedDeletes++;
                } else {
                    $errMsg = is_array($json) ? ($json['message'] ?? 'Lỗi không xác định') : 'HTTP '.$res->status();
                    $deleteErrors[] = "Mã {$del->code}: {$errMsg}";
                }
            } catch (Exception $e) {
                Log::warning('Fallback delete error: '.$e->getMessage());
                $deleteErrors[] = "Mã {$del->code}: ".$e->getMessage();
            }
        }

        // 2. Process updates
        foreach ($updates as $upd) {
            try {
                $res = Http::timeout(10)->withOptions(['allow_redirects' => true])
                    ->post($config->apps_script_url, [
                        'action' => 'update',
                        'sheet' => $config->sheet_name,
                        'key' => $upd->code,
                        'row' => $upd->toSheetRow(),
                        'keyColumn' => $keyColumnLetter,
                        'keyColumnIndex' => $keyColumnIndex,
                    ]);
                $json = $res->json();
                if ($res->successful() && is_array($json) && ($json['success'] ?? false)) {
                    $upd->updateQuietly([
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'google_sheet_config_id' => $config->id,
                    ]);
                    $syncedUpdates++;
                } else {
                    $errMsg = is_array($json) ? ($json['message'] ?? 'Lỗi không xác định') : 'HTTP '.$res->status();
                    $updateErrors[] = "Mã {$upd->code}: {$errMsg}";
                }
            } catch (Exception $e) {
                Log::warning('Fallback update error: '.$e->getMessage());
                $updateErrors[] = "Mã {$upd->code}: ".$e->getMessage();
            }
        }

        // 3. Process creates
        foreach ($creates as $cr) {
            try {
                $res = Http::timeout(10)->withOptions(['allow_redirects' => true])
                    ->post($config->apps_script_url, [
                        'action' => 'append',
                        'sheet' => $config->sheet_name,
                        'row' => $cr->toSheetRow(),
                        'key' => $cr->code,
                        'keyColumn' => $keyColumnLetter,
                        'keyColumnIndex' => $keyColumnIndex,
                    ]);
                $json = $res->json();
                if ($res->successful() && is_array($json) && ($json['success'] ?? false)) {
                    $cr->updateQuietly([
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'google_sheet_config_id' => $config->id,
                    ]);
                    $syncedCreates++;
                } else {
                    $errMsg = is_array($json) ? ($json['message'] ?? 'Lỗi không xác định') : 'HTTP '.$res->status();
                    $createErrors[] = "Mã {$cr->code}: {$errMsg}";
                }
            } catch (Exception $e) {
                Log::warning('Fallback append error: '.$e->getMessage());
                $createErrors[] = "Mã {$cr->code}: ".$e->getMessage();
            }
        }

        $totalRequested = $creates->count() + $updates->count() + $deletes->count();
        $totalSynced = $syncedCreates + $syncedUpdates + $syncedDeletes;

        if ($totalSynced > 0) {
            $config->update(['last_synced_at' => now()]);
        }

        $allErrors = array_merge($deleteErrors, $updateErrors, $createErrors);
        $hasErrors = ! empty($allErrors);

        if (! $hasErrors && $totalSynced === $totalRequested && $totalRequested > 0) {
            return [
                'success' => true,
                'total' => $totalSynced,
                'creates' => $syncedCreates,
                'updates' => $syncedUpdates,
                'deletes' => $syncedDeletes,
                'message' => "Đã đẩy {$totalSynced} thay đổi ở chế độ tương thích (Thêm: {$syncedCreates}, Sửa: {$syncedUpdates}, Xoá: {$syncedDeletes}). Khuyên dùng: Cập nhật Apps Script v2.1 để tối ưu hoá.",
            ];
        }

        // Detect if error was caused by legacy Apps Script missing actions
        $isInvalidAction = false;
        foreach ($allErrors as $err) {
            if (str_contains($err, 'Hành động không hợp lệ') || str_contains($err, 'Hành động không xác định')) {
                $isInvalidAction = true;
                break;
            }
        }

        if ($isInvalidAction) {
            $msg = "Không thể xoá/sửa trên Google Sheet vì Webhook Google Apps Script báo lỗi: 'Hành động không hợp lệ' (do Webhook đang chạy phiên bản cũ chưa hỗ trợ Xoá theo mã). "
                 .'Vui lòng cập nhật mã Apps Script mới nhất trên Google Sheet (Tiện ích mở rộng > Apps Script, dán mã mới rồi chọn Triển khai > Quản lý bản triển khai > Chỉnh sửa ✏️ > Phiên bản: "Phiên bản mới" > Triển khai).';
        } else {
            $msg = "Đã đẩy {$totalSynced}/{$totalRequested} thay đổi. Lỗi: ".implode('; ', array_slice($allErrors, 0, 3));
        }

        return [
            'success' => false,
            'total' => $totalSynced,
            'creates' => $syncedCreates,
            'updates' => $syncedUpdates,
            'deletes' => $syncedDeletes,
            'message' => $msg,
        ];
    }

    /**
     * Check if the row contains column headers.
     *
     * @param  array<int, mixed>  $row
     */
    protected function isHeaderRow(array $row, string $type): bool
    {
        $joined = mb_strtolower(implode(' ', array_slice($row, 0, 5)));

        if ($type === 'customer' && (str_contains($joined, 'mã') || str_contains($joined, 'tên') || str_contains($joined, 'mst'))) {
            return true;
        }

        if ($type === 'supplier' && (str_contains($joined, 'mã') || str_contains($joined, 'npp') || str_contains($joined, 'mst'))) {
            return true;
        }

        if ($type === 'product' && (str_contains($joined, 'mã') || str_contains($joined, 'sản phẩm') || str_contains($joined, 'giá'))) {
            return true;
        }

        return false;
    }

    /**
     * Generate sample CSV for the model type.
     */
    public function generateCsvForType(string $type): string
    {
        $output = fopen('php://temp', 'r+');

        if ($type === 'customer') {
            fputcsv($output, Customer::sheetHeaders());
            foreach (Customer::all() as $customer) {
                fputcsv($output, $customer->toSheetRow());
            }
        } elseif ($type === 'supplier') {
            fputcsv($output, Supplier::sheetHeaders());
            foreach (Supplier::all() as $supplier) {
                fputcsv($output, $supplier->toSheetRow());
            }
        } elseif ($type === 'product') {
            fputcsv($output, Product::sheetHeaders());
            foreach (Product::all() as $product) {
                fputcsv($output, $product->toSheetRow());
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return (string) $csv;
    }

    /**
     * Complete Google Apps Script template for 2-way read/write/delete with change sync and duplicate prevention.
     */
    public static function getGoogleAppsScriptTemplate(): string
    {
        return <<<'JAVASCRIPT'
/**
 * OP-APP Google Sheets Connector Script (v2.1 - Hỗ trợ Đẩy thay đổi có đánh dấu / Thêm / Sửa / Xoá theo mã / Chống trùng mã)
 * 
 * HƯỚNG DẪN CÀI ĐẶT / NÂNG CẤP (QUAN TRỌNG):
 * 1. Mở file Google Sheet của bạn.
 * 2. Trên thanh menu, chọn: Tiện ích mở rộng (Extensions) > Apps Script.
 * 3. Xoá hết nội dung cũ trong tệp Code.gs và dán toàn bộ đoạn mã này vào. Bấm Lưu (Ctrl+S / Cmd+S).
 * 4. Bấm nút "Triển khai" (Deploy) màu xanh ở góc trên bên phải:
 *    ★ NẾU ĐÃ TỪNG TRIỂN KHAI TRƯỚC ĐÓ (BẮT BUỘC để kích hoạt mã mới):
 *      - Chọn "Quản lý bản triển khai" (Manage deployments)
 *      - Bấm vào icon Bút chì ✏️ (Chỉnh sửa / Edit) ở góc trên bên phải của bản triển khai Web App
 *      - Tại mục "Phiên bản" (Version), click chọn "Phiên bản mới" (New version)
 *      - Bấm nút "Triển khai" (Deploy) màu xanh bên dưới.
 *    ★ NẾU LẦN ĐẦU TRIỂN KHAI MỚI:
 *      - Chọn "Bản triển khai mới" (New deployment) > Loại: "Ứng dụng web" (Web app)
 *      - Thực thi dưới dạng (Execute as): "Tôi" (Me)
 *      - Ai có quyền truy cập (Who has access): "Bất kỳ ai" (Anyone)
 *      - Bấm "Triển khai" (Deploy), sao chép URL kết thúc bằng /exec và dán vào Cấu hình trên OP-APP.
 */

function doGet(e) {
  try {
    var sheetName = (e && e.parameter && e.parameter.sheet) ? e.parameter.sheet : "Khách hàng";
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(sheetName) || ss.getSheets()[0];
    var data = sheet.getDataRange().getValues();
    
    return jsonResponse({
      success: true,
      sheet: sheet.getName(),
      rows: data
    });
  } catch (err) {
    return jsonResponse({ success: false, message: "Lỗi doGet: " + err.toString() });
  }
}

function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return jsonResponse({ success: false, message: "Không tìm thấy nội dung gửi lên (postData)" });
    }
    
    var params = JSON.parse(e.postData.contents);
    var sheetName = params.sheet || "Khách hàng";
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(sheetName);
    
    if (!sheet) {
      sheet = ss.insertSheet(sheetName);
    }
    
    var action = params.action;

    // Xác định cột khoá chính (keyColumn: "A", "B"... hoặc keyColumnIndex: 0, 1...)
    var keyCol = 0;
    if (params.keyColumnIndex !== undefined && !isNaN(Number(params.keyColumnIndex))) {
      keyCol = Number(params.keyColumnIndex);
    } else if (params.keyColumn && typeof params.keyColumn === 'string') {
      var colLetter = params.keyColumn.trim().toUpperCase();
      if (/^[A-Z]+$/.test(colLetter)) {
        var colIdx = 0;
        for (var k = 0; k < colLetter.length; k++) {
          colIdx = colIdx * 26 + (colLetter.charCodeAt(k) - 64);
        }
        keyCol = Math.max(0, colIdx - 1);
      }
    }

    // Helper: Tìm dòng theo mã (trả về 1-based row index hoặc -1)
    function findRowByKey(targetKey) {
      var search = String(targetKey || '').trim().toLowerCase();
      if (!search) return -1;
      var data = sheet.getDataRange().getValues();
      for (var i = 1; i < data.length; i++) {
        if (String(data[i][keyCol]).trim().toLowerCase() === search) {
          return i + 1; // 1-based index cho Range/Sheet
        }
      }
      return -1;
    }

    // 1. ĐỒNG BỘ CÁC THAY ĐỔI CÓ ĐÁNH DẤU (SYNC_CHANGES)
    if (action === 'sync_changes') {
      var creates = params.creates || [];
      var updates = params.updates || [];
      var deletes = params.deletes || [];
      var deletedCount = 0;
      var updatedCount = 0;
      var createdCount = 0;

      // Bước 1: Xử lý XOÁ (Xoá từ dưới lên để không làm nhảy thứ tự dòng)
      if (deletes && deletes.length > 0) {
        var deleteMap = {};
        for (var d = 0; d < deletes.length; d++) {
          deleteMap[String(deletes[d]).trim().toLowerCase()] = true;
        }
        var currentData = sheet.getDataRange().getValues();
        for (var i = currentData.length - 1; i >= 1; i--) {
          var rowKey = String(currentData[i][keyCol]).trim().toLowerCase();
          if (deleteMap[rowKey]) {
            sheet.deleteRow(i + 1);
            deletedCount++;
          }
        }
      }

      // Bước 2: Xử lý CẬP NHẬT (Tìm dòng theo mã và ghi đè)
      if (updates && updates.length > 0) {
        for (var u = 0; u < updates.length; u++) {
          var item = updates[u];
          var updateRow = item.row;
          if (!updateRow || !Array.isArray(updateRow)) continue;
          
          var lookupKey = item.originalKey || item.key || updateRow[keyCol];
          var foundRow = findRowByKey(lookupKey);
          if (foundRow > 0) {
            sheet.getRange(foundRow, 1, 1, updateRow.length).setValues([updateRow]);
            updatedCount++;
          } else {
            // Nếu không tìm thấy mã cũ, kiểm tra mã mới đã có chưa
            var newKey = updateRow[keyCol];
            var checkNew = findRowByKey(newKey);
            if (checkNew > 0) {
              sheet.getRange(checkNew, 1, 1, updateRow.length).setValues([updateRow]);
            } else {
              sheet.appendRow(updateRow);
            }
            updatedCount++;
          }
        }
      }

      // Bước 3: Xử lý THÊM MỚI (KIỂM TRA TRÙNG MÃ: Nếu đã có thì cập nhật, chưa có mới append)
      if (creates && creates.length > 0) {
        for (var c = 0; c < creates.length; c++) {
          var createRow = creates[c];
          if (!createRow || !Array.isArray(createRow)) continue;
          
          var createKey = createRow[keyCol];
          var existingRow = findRowByKey(createKey);
          if (existingRow > 0) {
            // Đã tồn tại mã trên Google Sheet -> Cập nhật đè lên dòng đó, KHÔNG append tạo dòng trùng!
            sheet.getRange(existingRow, 1, 1, createRow.length).setValues([createRow]);
          } else {
            sheet.appendRow(createRow);
          }
          createdCount++;
        }
      }

      return jsonResponse({
        success: true,
        message: "Đồng bộ thay đổi thành công! Thêm: " + createdCount + ", Sửa: " + updatedCount + ", Xoá: " + deletedCount,
        created: createdCount,
        updated: updatedCount,
        deleted: deletedCount
      });
    }

    // 2. THÊM MỚI (APPEND) - Chống trùng mã
    if (action === 'append') {
      if (!params.row || !Array.isArray(params.row)) {
        return jsonResponse({ success: false, message: "Dữ liệu dòng (row) không hợp lệ" });
      }
      var appendKey = params.row[keyCol];
      var existingRow = findRowByKey(appendKey);
      if (existingRow > 0) {
        sheet.getRange(existingRow, 1, 1, params.row.length).setValues([params.row]);
        return jsonResponse({ success: true, message: "Mã '" + appendKey + "' đã tồn tại, đã cập nhật đè lên dòng " + existingRow, action: "update", row: existingRow });
      }
      sheet.appendRow(params.row);
      return jsonResponse({ success: true, message: "Đã thêm dòng mới thành công trên Google Sheet", action: "append" });
    }

    // 3. SỬA DÒNG (UPDATE)
    if (action === 'update') {
      var searchKey = String(params.originalKey || params.key || '').trim().toLowerCase();
      var foundRowIndex = findRowByKey(searchKey);
      
      if (foundRowIndex > 0) {
        sheet.getRange(foundRowIndex, 1, 1, params.row.length).setValues([params.row]);
        return jsonResponse({ success: true, message: "Đã cập nhật dòng " + foundRowIndex + " trên Google Sheet", action: "update", row: foundRowIndex });
      } else {
        var newKey = Array.isArray(params.row) ? params.row[keyCol] : '';
        var checkExist = findRowByKey(newKey);
        if (checkExist > 0) {
          sheet.getRange(checkExist, 1, 1, params.row.length).setValues([params.row]);
          return jsonResponse({ success: true, message: "Đã cập nhật dòng " + checkExist + " trên Google Sheet", action: "update", row: checkExist });
        }
        sheet.appendRow(params.row);
        return jsonResponse({ success: true, message: "Không tìm thấy dòng cũ, đã thêm mới vào Google Sheet", action: "append" });
      }
    }

    // 4. XOÁ DÒNG THEO MÃ (DELETE) - Quét từ dưới lên, xoá tất cả dòng khớp mã
    if (action === 'delete') {
      var searchKey = String(params.key || '').trim().toLowerCase();
      if (!searchKey) {
        return jsonResponse({ success: false, message: "Thiếu mã cần xoá (key)" });
      }
      var currentData = sheet.getDataRange().getValues();
      var deletedRows = 0;
      for (var i = currentData.length - 1; i >= 1; i--) {
        if (String(currentData[i][keyCol]).trim().toLowerCase() === searchKey) {
          sheet.deleteRow(i + 1);
          deletedRows++;
        }
      }
      if (deletedRows > 0) {
        return jsonResponse({ 
          success: true, 
          message: "Đã xoá thành công " + deletedRows + " dòng có mã '" + params.key + "' trên Google Sheet", 
          action: "delete", 
          deleted: deletedRows 
        });
      } else {
        return jsonResponse({ 
          success: true, 
          message: "Mã '" + params.key + "' không có trên Google Sheet (coi như đã xoá)", 
          action: "delete", 
          deleted: 0 
        });
      }
    }

    // 5. ĐỒNG BỘ TOÀN BỘ (SYNC_ALL)
    if (action === 'sync_all') {
      sheet.clearContents();
      if (params.headers && params.headers.length > 0) {
        sheet.appendRow(params.headers);
      }
      if (params.rows && params.rows.length > 0) {
        var range = sheet.getRange(2, 1, params.rows.length, params.rows[0].length);
        range.setValues(params.rows);
      }
      return jsonResponse({ success: true, message: "Đã đồng bộ toàn bộ bảng lên Google Sheet", action: "sync_all" });
    }

    return jsonResponse({ success: false, message: "Hành động không xác định: " + action });
  } catch (err) {
    return jsonResponse({ success: false, message: "Lỗi thực thi doPost: " + err.toString() });
  }
}

function jsonResponse(data) {
  return ContentService.createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}
JAVASCRIPT;
    }
}
