<?php

namespace App\Http\Controllers;

use App\Models\GoogleSheetConfig;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GoogleSheetConfigController extends Controller
{
    /**
     * Display a listing of Google Sheet configurations.
     */
    public function index(): View
    {
        $configs = GoogleSheetConfig::withCount(['customers', 'suppliers', 'products'])
            ->orderBy('created_at', 'desc')
            ->get();

        $appsScriptCode = GoogleSheetService::getGoogleAppsScriptTemplate();

        return view('sheets.index', [
            'configs' => $configs,
            'appsScriptCode' => $appsScriptCode,
        ]);
    }

    /**
     * Store a newly created Google Sheet configuration.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'spreadsheet_url' => ['required', 'string'],
            'sheet_name' => ['required', 'string', 'max:255'],
            'sheet_type' => ['required', Rule::in(['customer', 'supplier', 'product', 'custom'])],
            'range' => ['nullable', 'string', 'max:50'],
            'primary_key_column' => ['nullable', 'string', 'max:10'],
            'sync_mode' => ['required', Rule::in(['csv_public', 'api_key', 'apps_script'])],
            'api_key' => ['nullable', 'string', 'max:255'],
            'apps_script_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Vui lòng nhập tên nhận diện liên kết.',
            'spreadsheet_url.required' => 'Vui lòng nhập đường link Google Sheet.',
            'sheet_name.required' => 'Vui lòng nhập tên sheet/tab trong Google Sheet.',
            'sheet_type.required' => 'Vui lòng chọn loại dữ liệu quản lý.',
        ]);

        $spreadsheetId = GoogleSheetConfig::extractSpreadsheetId($validated['spreadsheet_url']);
        $gid = GoogleSheetConfig::extractGid($validated['spreadsheet_url']);
        $primaryKeyCol = strtoupper(trim((string) ($request->input('primary_key_column', 'A'))));
        if ($primaryKeyCol === '') {
            $primaryKeyCol = 'A';
        }

        $config = GoogleSheetConfig::create(array_merge($validated, [
            'spreadsheet_id' => $spreadsheetId,
            'gid' => $gid,
            'range' => $validated['range'] ?? 'A1:Z',
            'primary_key_column' => $primaryKeyCol,
            'is_active' => true,
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã lưu cấu hình Google Sheet thành công!',
                'data' => $config,
            ]);
        }

        return redirect()->route('sheets.index')->with('success', "Đã kết nối Google Sheet \"{$config->name}\" thành công!");
    }

    /**
     * Show config details for edit modal.
     */
    public function show(GoogleSheetConfig $sheetConfig): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $sheetConfig,
        ]);
    }

    /**
     * Update the specified Google Sheet configuration.
     */
    public function update(Request $request, GoogleSheetConfig $sheetConfig): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'spreadsheet_url' => ['required', 'string'],
            'sheet_name' => ['required', 'string', 'max:255'],
            'sheet_type' => ['required', Rule::in(['customer', 'supplier', 'product', 'custom'])],
            'range' => ['nullable', 'string', 'max:50'],
            'primary_key_column' => ['nullable', 'string', 'max:10'],
            'sync_mode' => ['required', Rule::in(['csv_public', 'api_key', 'apps_script'])],
            'api_key' => ['nullable', 'string', 'max:255'],
            'apps_script_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $spreadsheetId = GoogleSheetConfig::extractSpreadsheetId($validated['spreadsheet_url']);
        $gid = GoogleSheetConfig::extractGid($validated['spreadsheet_url']);
        $primaryKeyCol = strtoupper(trim((string) ($request->input('primary_key_column', 'A'))));
        if ($primaryKeyCol === '') {
            $primaryKeyCol = 'A';
        }

        $sheetConfig->update(array_merge($validated, [
            'spreadsheet_id' => $spreadsheetId,
            'gid' => $gid,
            'primary_key_column' => $primaryKeyCol,
            'is_active' => $request->boolean('is_active', true),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật cấu hình Google Sheet thành công!',
                'data' => $sheetConfig,
            ]);
        }

        return redirect()->route('sheets.index')->with('success', "Đã cập nhật cấu hình \"{$sheetConfig->name}\" thành công!");
    }

    /**
     * Remove the specified configuration.
     */
    public function destroy(Request $request, GoogleSheetConfig $sheetConfig): RedirectResponse|JsonResponse
    {
        $name = $sheetConfig->name;
        $sheetConfig->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã xoá cấu hình \"{$name}\" thành công!",
            ]);
        }

        return redirect()->route('sheets.index')->with('success', "Đã xoá cấu hình \"{$name}\" thành công!");
    }

    /**
     * Trigger manual sync for a specific sheet configuration.
     */
    public function sync(Request $request, GoogleSheetConfig $sheetConfig, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        try {
            $direction = $request->input('direction', 'pull');
            if ($direction === 'push') {
                $result = $sheetService->pushAllToSheet($sheetConfig);
            } else {
                $result = $sheetService->syncFromSheet($sheetConfig);
            }

            if ($request->wantsJson()) {
                $status = ($result['success'] ?? false) ? 200 : 422;

                return response()->json($result, $status);
            }

            if (! ($result['success'] ?? false)) {
                return redirect()->back()->with('error', $result['message']);
            }

            return redirect()->back()->with('success', $result['message']);
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', 'Lỗi đồng bộ: '.$e->getMessage());
        }
    }

    /**
     * Export local data to CSV format matching Google Sheet columns.
     */
    public function exportCsv(Request $request, GoogleSheetService $sheetService): Response
    {
        $type = $request->input('type', 'customer');
        $csvContent = $sheetService->generateCsvForType($type);
        $filename = "OP_{$type}_export_".now()->format('Ymd_His').'.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
