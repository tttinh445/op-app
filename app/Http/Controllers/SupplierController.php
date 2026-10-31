<?php

namespace App\Http\Controllers;

use App\Models\GoogleSheetConfig;
use App\Models\Supplier;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request, GoogleSheetService $sheetService): View|JsonResponse
    {
        $query = Supplier::query()->with('googleSheetConfig');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('zalo', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['code', 'name', 'type', 'tax_id', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        if (! in_array(strtolower($sortDir), ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $suppliers = $query->orderBy($sortBy, $sortDir)->paginate(15)->withQueryString();
        $sheetConfigs = GoogleSheetConfig::where('sheet_type', 'supplier')->where('is_active', true)->get();
        $pendingChanges = $sheetService->getPendingChangesSummary('supplier');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'pending_changes' => $pendingChanges,
            ]);
        }

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'sheetConfigs' => $sheetConfigs,
            'pendingChanges' => $pendingChanges,
            'currentSearch' => $search,
            'currentType' => $type,
            'currentSortBy' => $sortBy,
            'currentSortDir' => $sortDir,
        ]);
    }

    /**
     * Search suppliers for dropdown/combobox (server-side search).
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $query = Supplier::query()->select(['id', 'code', 'name', 'type', 'tax_id']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('tax_id', 'like', "%{$q}%");
            });
        }

        $suppliers = $query->orderBy('name', 'asc')->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $suppliers->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'type' => $s->type,
                'tax_id' => $s->tax_id,
                'display' => "{$s->name} ({$s->code})",
            ]),
        ]);
    }

    /**
     * Check if supplier code already exists (for blur validation).
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $ignoreId = $request->input('ignore_id');

        if ($code === '') {
            return response()->json(['exists' => false]);
        }

        $query = Supplier::where('code', $code);
        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Mã nhà cung cấp/NPP này đã tồn tại trên hệ thống.' : 'Mã hợp lệ.',
        ]);
    }

    /**
     * Store a newly created supplier and sync to Google Sheet.
     */
    public function store(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['NPP', 'NCC'])],
            'zalo' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['required', 'string', 'max:50', 'unique:suppliers,tax_id'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.required' => 'Vui lòng nhập Mã nhà cung cấp/NPP.',
            'code.unique' => 'Mã này đã tồn tại trên hệ thống.',
            'name.required' => 'Vui lòng nhập Tên NPP/NCC.',
            'type.required' => 'Vui lòng chọn Loại (NPP hoặc NCC).',
            'type.in' => 'Loại chỉ được chọn NPP hoặc NCC.',
            'tax_id.required' => 'Vui lòng nhập Mã số thuế (MST).',
            'tax_id.unique' => 'Mã số thuế (MST) này đã tồn tại trên hệ thống.',
            'email.email' => 'Địa chỉ Email không đúng định dạng.',
        ]);

        $validated['sync_status'] = 'pending_create';
        $supplier = Supplier::create($validated);

        // Auto sync append to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($supplier, 'append');
        if ($syncResult['synced']) {
            $supplier->updateQuietly(['sync_status' => 'synced', 'synced_at' => now()]);
        }

        $message = "Đã thêm mới NPP/NCC {$supplier->name} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Dữ liệu đã được thêm lên Google Sheet)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $supplier,
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('suppliers.index')->with('success', $message);
    }

    /**
     * Return supplier data for editing via modal.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update the specified supplier and sync to Google Sheet.
     */
    public function update(Request $request, Supplier $supplier, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['NPP', 'NCC'])],
            'zalo' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'tax_id')->ignore($supplier->id)],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.required' => 'Vui lòng nhập Mã nhà cung cấp/NPP.',
            'code.unique' => 'Mã này đã tồn tại trên hệ thống.',
            'name.required' => 'Vui lòng nhập Tên NPP/NCC.',
            'type.required' => 'Vui lòng chọn Loại (NPP hoặc NCC).',
            'type.in' => 'Loại chỉ được chọn NPP hoặc NCC.',
            'tax_id.required' => 'Vui lòng nhập Mã số thuế (MST).',
            'tax_id.unique' => 'Mã số thuế (MST) này đã tồn tại trên hệ thống.',
            'email.email' => 'Địa chỉ Email không đúng định dạng.',
        ]);

        $originalCode = $supplier->code;
        $newStatus = $supplier->sync_status === 'pending_create' ? 'pending_create' : 'pending_update';
        $validated['sync_status'] = $newStatus;
        $supplier->update($validated);

        // Auto sync update to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($supplier, 'update', $originalCode);
        if ($syncResult['synced']) {
            $supplier->updateQuietly(['sync_status' => 'synced', 'synced_at' => now()]);
        }

        $message = "Đã cập nhật thông tin {$supplier->name} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Google Sheet đã được cập nhật)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $supplier,
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('suppliers.index')->with('success', $message);
    }

    /**
     * Remove the specified supplier and sync deletion to Google Sheet.
     */
    public function destroy(Request $request, Supplier $supplier, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $name = $supplier->name;
        $code = $supplier->code;
        $configId = $supplier->google_sheet_config_id;

        // Record pending delete
        $pendingDelete = $sheetService->recordPendingDelete('supplier', $code, $name, $configId);

        $supplier->delete();

        // Auto sync delete to Google Sheet
        $syncResult = $sheetService->syncDeleteToSheet('supplier', $code, $configId);
        if ($syncResult['synced']) {
            $pendingDelete->update(['status' => 'synced']);
        }

        $message = "Đã xoá NPP/NCC {$name} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Dòng tương ứng trên Google Sheet đã được xoá)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('suppliers.index')->with('success', $message);
    }

    /**
     * Trigger Google Sheet sync for suppliers (pull, push marked, or push all).
     */
    public function sync(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $configId = $request->input('config_id');
        $config = $configId
            ? GoogleSheetConfig::find($configId)
            : GoogleSheetConfig::where('sheet_type', 'supplier')->where('is_active', true)->first();

        if (! $config) {
            return redirect()->route('suppliers.index')->with('error', 'Chưa có cấu hình Google Sheet cho Nhà cung cấp. Vui lòng thêm liên kết Google Sheet trước.');
        }

        try {
            $direction = $request->input('direction', 'push');
            if ($direction === 'push_all') {
                $result = $sheetService->pushAllToSheet($config);
            } elseif ($direction === 'push' || $direction === 'push_marked') {
                $result = $sheetService->pushMarkedChanges($config);
            } else {
                $result = $sheetService->syncFromSheet($config);
            }

            if ($request->wantsJson()) {
                $status = ($result['success'] ?? false) ? 200 : 422;

                return response()->json($result, $status);
            }

            if (! ($result['success'] ?? false)) {
                return redirect()->route('suppliers.index')->with('error', $result['message']);
            }

            return redirect()->route('suppliers.index')->with('success', $result['message']);
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->route('suppliers.index')->with('error', 'Lỗi đồng bộ: '.$e->getMessage());
        }
    }
}
