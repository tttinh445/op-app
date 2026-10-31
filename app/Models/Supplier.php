<?php

namespace App\Models;

use App\Contracts\GoogleSheetSyncable;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model implements GoogleSheetSyncable
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'zalo',
        'tax_id',
        'bank_account',
        'bank_name',
        'address',
        'phone',
        'email',
        'website',
        'facebook',
        'note',
        'google_sheet_config_id',
        'synced_at',
        'sync_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relationship to Google Sheet configuration.
     */
    public function googleSheetConfig(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetConfig::class);
    }

    /**
     * Define the column headers in Google Sheet.
     *
     * @return array<int, string>
     */
    public static function sheetHeaders(): array
    {
        return [
            'Mã',
            'Tên NPP/NCC',
            'Loại',
            'Zalo',
            'MST',
            'STK',
            'Ngân hàng',
            'Địa chỉ',
            'Điện thoại',
            'Email',
            'Ghi chú',
            'Website',
            'FB',
            'Dấu thời gian',
        ];
    }

    /**
     * Map model attributes to Google Sheet row values.
     *
     * @return array<int, mixed>
     */
    public function toSheetRow(): array
    {
        return [
            $this->code,
            $this->name,
            $this->type,
            $this->zalo ?? '',
            $this->tax_id,
            $this->bank_account ?? '',
            $this->bank_name ?? '',
            $this->address ?? '',
            $this->phone ?? '',
            $this->email ?? '',
            $this->note ?? '',
            $this->website ?? '',
            $this->facebook ?? '',
            $this->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Map raw row data from Google Sheet into model attributes.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public static function fromSheetRow(array $row): array
    {
        return [
            'code' => trim((string) ($row[1] ?? '')),
            'name' => trim((string) ($row[2] ?? '')),
            'type' => in_array(trim((string) ($row[3] ?? '')), ['NPP', 'NCC'], true)
                ? trim((string) $row[3])
                : 'NCC',
            'zalo' => trim((string) ($row[4] ?? '')) ?: null,
            'tax_id' => trim((string) ($row[5] ?? '')),
            'bank_account' => trim((string) ($row[6] ?? '')) ?: null,
            'bank_name' => trim((string) ($row[7] ?? '')) ?: null,
            'address' => trim((string) ($row[8] ?? '')) ?: null,
            'phone' => trim((string) ($row[9] ?? '')) ?: null,
            'email' => trim((string) ($row[10] ?? '')) ?: null,
            'website' => trim((string) ($row[11] ?? '')) ?: null,
            'facebook' => trim((string) ($row[12] ?? '')) ?: null,
            'note' => trim((string) ($row[13] ?? '')) ?: null,
        ];
    }

    /**
     * Unique identifier for the sheet type.
     */
    public static function sheetType(): string
    {
        return 'supplier';
    }
}
