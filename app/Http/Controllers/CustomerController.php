<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\GoogleSheetConfig;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    /**
     * Display a listing of customers.
     */
    public function index(Request $request, GoogleSheetService $sheetService): View|JsonResponse
    {
        $query = Customer::query()->with('googleSheetConfig');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Whitelist sort fields
        $allowedSorts = ['code', 'name', 'type', 'tax_id', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        if (! in_array(strtolower($sortDir), ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $customers = $query->orderBy($sortBy, $sortDir)->paginate(15)->withQueryString();
        $sheetConfigs = GoogleSheetConfig::where('sheet_type', 'customer')->where('is_active', true)->get();
        $pendingChanges = $sheetService->getPendingChangesSummary('customer');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $customers,
                'pending_changes' => $pendingChanges,
            ]);
        }

        return view('customers.index', [
            'customers' => $customers,
            'sheetConfigs' => $sheetConfigs,
            'pendingChanges' => $pendingChanges,
            'currentSearch' => $search,
            'currentType' => $type,
            'currentSortBy' => $sortBy,
            'currentSortDir' => $sortDir,
        ]);
    }

    /**
     * Check if customer code already exists (for blur validation).
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $ignoreId = $request->input('ignore_id');

        if ($code === '') {
            return response()->json(['exists' => false]);
        }

        $query = Customer::where('code', $code);
        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Mã khách hàng này đã tồn tại trên hệ thống.' : 'Mã hợp lệ.',
        ]);
    }

    /**
     * Store a newly created customer and sync to Google Sheet.
     */
    public function store(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['Khách vãng lai', 'Công ty'])],
            'tax_id' => ['required', 'string', 'max:50', 'unique:customers,tax_id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.required' => 'Vui lòng nhập Mã khách hàng.',
            'code.unique' => 'Mã khách hàng này đã tồn tại trên hệ thống.',
            'name.required' => 'Vui lòng nhập Tên khách hàng.',
            'type.required' => 'Vui lòng chọn Loại khách hàng.',
            'type.in' => 'Loại khách hàng chỉ được chọn Khách vãng lai hoặc Công ty.',
            'tax_id.required' => 'Vui lòng nhập Mã số thuế (MST).',
            'tax_id.unique' => 'Mã số thuế (MST) này đã tồn tại trên hệ thống.',
            'email.email' => 'Địa chỉ Email không đúng định dạng.',
        ]);

        $validated['sync_status'] = 'pending_create';
        $customer = Customer::create($validated);

        // Auto sync append to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($customer, 'append');
        if ($syncResult['synced']) {
            $customer->updateQuietly(['sync_status' => 'synced', 'synced_at' => now()]);
        }

        $message = "Đã thêm mới khách hàng {$customer->name} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Dữ liệu đã được thêm lên Google Sheet)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $customer,
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('customers.index')->with('success', $message);
    }

    /**
     * Return customer data for editing via modal.
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    /**
     * Update the specified customer and sync to Google Sheet.
     */
    public function update(Request $request, Customer $customer, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($customer->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['Khách vãng lai', 'Công ty'])],
            'tax_id' => ['required', 'string', 'max:50', Rule::unique('customers', 'tax_id')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.required' => 'Vui lòng nhập Mã khách hàng.',
            'code.unique' => 'Mã khách hàng này đã tồn tại trên hệ thống.',
            'name.required' => 'Vui lòng nhập Tên khách hàng.',
            'type.required' => 'Vui lòng chọn Loại khách hàng.',
            'type.in' => 'Loại khách hàng chỉ được chọn Khách vãng lai hoặc Công ty.',
            'tax_id.required' => 'Vui lòng nhập Mã số thuế (MST).',
            'tax_id.unique' => 'Mã số thuế (MST) này đã tồn tại trên hệ thống.',
            'email.email' => 'Địa chỉ Email không đúng định dạng.',
        ]);

        $originalCode = $customer->code;
        $newStatus = $customer->sync_status === 'pending_create' ? 'pending_create' : 'pending_update';
        $validated['sync_status'] = $newStatus;
        $customer->update($validated);

        // Auto sync update to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($customer, 'update', $originalCode);
        if ($syncResult['synced']) {
            $customer->updateQuietly(['sync_status' => 'synced', 'synced_at' => now()]);
        }

        $message = "Đã cập nhật thông tin khách hàng {$customer->name} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Google Sheet đã được cập nhật)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $customer,
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('customers.index')->with('success', $message);
    }

    /**
     * Remove the specified customer and sync deletion to Google Sheet.
     */
    public function destroy(Request $request, Customer $customer, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $name = $customer->name;
        $code = $customer->code;
        $configId = $customer->google_sheet_config_id;

        // Record pending delete
        $pendingDelete = $sheetService->recordPendingDelete('customer', $code, $name, $configId);

        $customer->delete();

        // Auto sync delete to Google Sheet
        $syncResult = $sheetService->syncDeleteToSheet('customer', $code, $configId);
        if ($syncResult['synced']) {
            $pendingDelete->update(['status' => 'synced']);
        }

        $message = "Đã xoá khách hàng {$name} thành công!";
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

        return redirect()->route('customers.index')->with('success', $message);
    }

    /**
     * Trigger Google Sheet sync for customers (pull, push marked, or push all).
     */
    public function sync(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $configId = $request->input('config_id');
        $config = $configId
            ? GoogleSheetConfig::find($configId)
            : GoogleSheetConfig::where('sheet_type', 'customer')->where('is_active', true)->first();

        if (! $config) {
            return redirect()->route('customers.index')->with('error', 'Chưa có cấu hình Google Sheet cho Khách hàng. Vui lòng thêm liên kết Google Sheet trước.');
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
                return redirect()->route('customers.index')->with('error', $result['message']);
            }

            return redirect()->route('customers.index')->with('success', $result['message']);
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->route('customers.index')->with('error', 'Lỗi đồng bộ: '.$e->getMessage());
        }
    }
}
