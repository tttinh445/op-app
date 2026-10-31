<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\GoogleSheetConfig;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContractController extends Controller
{
    /**
     * Display a listing of contracts with server-side pagination (30 items/page).
     * Default sort: contract_date desc.
     */
    public function index(Request $request, GoogleSheetService $sheetService): View|JsonResponse
    {
        $query = Contract::query()->with(['partnerRecord', 'googleSheetConfig']);

        // Filter: Mã
        if ($code = trim((string) $request->input('code', ''))) {
            $query->where('code', 'like', "%{$code}%");
        }

        // Filter: Đối tác (mã hoặc tên)
        if ($partner = trim((string) $request->input('partner', ''))) {
            $query->where(function ($q) use ($partner) {
                $q->where('partner', 'like', "%{$partner}%")
                    ->orWhereHas('partnerRecord', function ($sq) use ($partner) {
                        $sq->where('name', 'like', "%{$partner}%")
                            ->orWhere('code', 'like', "%{$partner}%");
                    });
            });
        }

        // Filter: Trạng thái
        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status', $status);
        }

        // Filter: NV Sale
        if ($salesperson = trim((string) $request->input('salesperson', ''))) {
            $query->where('salesperson', $salesperson);
        }

        // Filter: Thanh toán cho Sale
        if ($salePaymentStatus = trim((string) $request->input('sale_payment_status', ''))) {
            $query->where('sale_payment_status', $salePaymentStatus);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'contract_date');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc'));

        $allowedSorts = [
            'contract_date',
            'code',
            'contract_number',
            'partner',
            'amount',
            'status',
            'salesperson',
            'sale_payment_status',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'contract_date';
        }
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $contracts = $query->orderBy($sortBy, $sortDir)->paginate(30)->withQueryString();

        // Load all employees without pagination for select filter & modals
        $employees = Employee::query()->orderBy('name', 'asc')->get(['id', 'code', 'name']);

        // Google Sheet configs and pending changes for Contracts
        $sheetConfigs = GoogleSheetConfig::where('sheet_type', 'contract')->where('is_active', true)->get();
        $pendingChanges = $sheetService->getPendingChangesSummary('contract');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $contracts,
                'pending_changes' => $pendingChanges,
            ]);
        }

        return view('contracts.index', [
            'contracts' => $contracts,
            'employees' => $employees,
            'sheetConfigs' => $sheetConfigs,
            'pendingChanges' => $pendingChanges,
            'currentCode' => $code,
            'currentPartner' => $partner,
            'currentStatus' => $status,
            'currentSalesperson' => $salesperson,
            'currentSalePaymentStatus' => $salePaymentStatus,
            'currentSortBy' => $sortBy,
            'currentSortDir' => $sortDir,
            'contractStatuses' => Contract::STATUSES,
            'contractTypes' => Contract::TYPES,
            'salePaymentStatuses' => Contract::SALE_PAYMENT_STATUSES,
            'activeTab' => 'contracts',
        ]);
    }

    /**
     * Check if contract code already exists (for blur validation).
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $ignoreId = $request->input('ignore_id');

        if ($code === '') {
            return response()->json(['exists' => false]);
        }

        $query = Contract::where('code', $code);
        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Mã hợp đồng này đã tồn tại trên hệ thống.' : 'Mã hợp lệ.',
        ]);
    }

    /**
     * Store a newly created contract and sync to Google Sheet.
     */
    public function store(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $this->sanitizeCurrencyInputs($request, ['amount', 'sale_discount']);
        $this->normalizeUrlInputs($request, ['link', 'quote_link']);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:contracts,code'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_date' => ['required', 'date'],
            'partner' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Contract::STATUSES)],
            'type' => ['required', Rule::in(Contract::TYPES)],
            'email' => ['nullable', 'email', 'max:255'],
            'link' => ['nullable', 'url', 'max:500'],
            'quote_link' => ['nullable', 'url', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'salesperson' => ['nullable', 'string', 'max:255'],
            'sale_discount' => ['nullable', 'numeric', 'min:0'],
            'sale_payment_status' => ['required', Rule::in(Contract::SALE_PAYMENT_STATUSES)],
        ], [
            'code.required' => 'Vui lòng nhập mã hợp đồng.',
            'code.unique' => 'Mã hợp đồng này đã tồn tại.',
            'contract_date.required' => 'Vui lòng chọn ngày hợp đồng.',
            'partner.required' => 'Vui lòng nhập hoặc chọn đối tác.',
            'link.url' => 'Link hợp đồng phải là định dạng URL hợp lệ.',
            'quote_link.url' => 'Link báo giá phải là định dạng URL hợp lệ.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
        ]);

        $amount = isset($validated['amount']) ? (float) $validated['amount'] : 0;
        $saleDiscount = $validated['sale_discount'] ?? null;
        if ($saleDiscount === null && $amount > 0) {
            $saleDiscount = Contract::calculateSaleDiscount($amount);
        }

        $contract = Contract::create([
            'code' => trim($validated['code']),
            'contract_number' => isset($validated['contract_number']) && trim((string) $validated['contract_number']) !== '' ? trim((string) $validated['contract_number']) : null,
            'contract_date' => $validated['contract_date'],
            'partner' => trim($validated['partner']),
            'amount' => $amount,
            'status' => $validated['status'] ?? 'Mới',
            'type' => $validated['type'] ?? 'Bán hàng',
            'email' => isset($validated['email']) && trim((string) $validated['email']) !== '' ? trim((string) $validated['email']) : null,
            'link' => isset($validated['link']) && trim((string) $validated['link']) !== '' ? trim((string) $validated['link']) : null,
            'quote_link' => isset($validated['quote_link']) && trim((string) $validated['quote_link']) !== '' ? trim((string) $validated['quote_link']) : null,
            'note' => isset($validated['note']) && trim((string) $validated['note']) !== '' ? trim((string) $validated['note']) : null,
            'salesperson' => isset($validated['salesperson']) && trim((string) $validated['salesperson']) !== '' ? trim((string) $validated['salesperson']) : null,
            'sale_discount' => $saleDiscount,
            'sale_payment_status' => $validated['sale_payment_status'] ?? 'Chưa thanh toán',
            'sync_status' => 'pending_create',
        ]);

        // Auto sync addition to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($contract, 'append');

        $message = "Thêm hợp đồng {$contract->code} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Đã đồng bộ lên Google Sheet)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $contract,
                'sheet_sync' => $syncResult,
            ], 201);
        }

        return redirect()->route('contracts.index')->with('success', $message);
    }

    /**
     * Display the specified contract in JSON format.
     */
    public function show(Contract $contract): JsonResponse
    {
        $contract->load(['partnerRecord', 'googleSheetConfig']);

        return response()->json([
            'success' => true,
            'data' => array_merge($contract->toArray(), [
                'contract_date_formatted' => $contract->contract_date ? $contract->contract_date->format('Y-m-d\TH:i') : '',
                'partner_display' => $contract->partner_display_name,
                'amount_formatted' => $contract->amount !== null ? number_format((float) $contract->amount, 0, ',', '.') : '0',
                'sale_discount_formatted' => $contract->sale_discount !== null ? number_format((float) $contract->sale_discount, 0, ',', '.') : '',
            ]),
        ]);
    }

    /**
     * Update the specified contract and sync to Google Sheet.
     */
    public function update(Request $request, Contract $contract, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $this->sanitizeCurrencyInputs($request, ['amount', 'sale_discount']);
        $this->normalizeUrlInputs($request, ['link', 'quote_link']);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('contracts', 'code')->ignore($contract->id)],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_date' => ['required', 'date'],
            'partner' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Contract::STATUSES)],
            'type' => ['required', Rule::in(Contract::TYPES)],
            'email' => ['nullable', 'email', 'max:255'],
            'link' => ['nullable', 'url', 'max:500'],
            'quote_link' => ['nullable', 'url', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'salesperson' => ['nullable', 'string', 'max:255'],
            'sale_discount' => ['nullable', 'numeric', 'min:0'],
            'sale_payment_status' => ['required', Rule::in(Contract::SALE_PAYMENT_STATUSES)],
        ], [
            'code.required' => 'Vui lòng nhập mã hợp đồng.',
            'code.unique' => 'Mã hợp đồng này đã tồn tại.',
            'contract_date.required' => 'Vui lòng chọn ngày hợp đồng.',
            'partner.required' => 'Vui lòng nhập hoặc chọn đối tác.',
            'link.url' => 'Link hợp đồng phải là định dạng URL hợp lệ.',
            'quote_link.url' => 'Link báo giá phải là định dạng URL hợp lệ.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
        ]);

        $originalCode = $contract->code;
        $newStatus = $contract->sync_status === 'pending_create' ? 'pending_create' : 'pending_update';

        $amount = isset($validated['amount']) ? (float) $validated['amount'] : 0;
        $saleDiscount = $validated['sale_discount'] ?? null;
        if ($saleDiscount === null && $amount > 0) {
            $saleDiscount = Contract::calculateSaleDiscount($amount);
        }

        $contract->update([
            'code' => trim($validated['code']),
            'contract_number' => isset($validated['contract_number']) && trim((string) $validated['contract_number']) !== '' ? trim((string) $validated['contract_number']) : null,
            'contract_date' => $validated['contract_date'],
            'partner' => trim($validated['partner']),
            'amount' => $validated['amount'] ?? 0,
            'status' => $validated['status'] ?? 'Mới',
            'type' => $validated['type'] ?? 'Bán hàng',
            'email' => isset($validated['email']) && trim((string) $validated['email']) !== '' ? trim((string) $validated['email']) : null,
            'link' => isset($validated['link']) && trim((string) $validated['link']) !== '' ? trim((string) $validated['link']) : null,
            'quote_link' => isset($validated['quote_link']) && trim((string) $validated['quote_link']) !== '' ? trim((string) $validated['quote_link']) : null,
            'note' => isset($validated['note']) && trim((string) $validated['note']) !== '' ? trim((string) $validated['note']) : null,
            'salesperson' => isset($validated['salesperson']) && trim((string) $validated['salesperson']) !== '' ? trim((string) $validated['salesperson']) : null,
            'sale_discount' => $validated['sale_discount'] ?? null,
            'sale_payment_status' => $validated['sale_payment_status'] ?? 'Chưa thanh toán',
            'sync_status' => $newStatus,
        ]);

        // Auto sync update to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($contract, 'update', $originalCode);

        $message = "Cập nhật hợp đồng {$contract->code} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Đã đồng bộ lên Google Sheet)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $contract,
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('contracts.index')->with('success', $message);
    }

    /**
     * Remove the specified contract and sync deletion to Google Sheet.
     */
    public function destroy(Request $request, Contract $contract, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $code = $contract->code;
        $name = $contract->contract_number ?: $contract->code;
        $configId = $contract->google_sheet_config_id;

        // Record pending delete
        $pendingDelete = $sheetService->recordPendingDelete('contract', $code, $name, $configId);

        $contract->delete();

        // Auto sync delete to Google Sheet
        $syncResult = $sheetService->syncDeleteToSheet('contract', $code, $configId);
        if ($syncResult['synced']) {
            $pendingDelete->update(['status' => 'synced']);
        }

        $message = "Đã xoá hợp đồng {$code} thành công!";
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

        return redirect()->route('contracts.index')->with('success', $message);
    }

    /**
     * Trigger Google Sheet sync for contracts (pull, push marked, or push all).
     */
    public function sync(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $configId = $request->input('config_id');
        $config = $configId
            ? GoogleSheetConfig::find($configId)
            : GoogleSheetConfig::where('sheet_type', 'contract')->where('is_active', true)->first();

        if (! $config) {
            return redirect()->route('contracts.index')->with('error', 'Chưa có cấu hình Google Sheet cho Hợp đồng. Vui lòng thêm liên kết Google Sheet trước.');
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
                return response()->json($result);
            }

            return redirect()->route('contracts.index')->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi đồng bộ: '.$e->getMessage(),
                ], 422);
            }

            return redirect()->route('contracts.index')->with('error', 'Lỗi đồng bộ: '.$e->getMessage());
        }
    }

    /**
     * Sanitize currency input strings (removing thousand dots, spaces, currency symbols).
     *
     * @param  list<string>  $keys
     */
    protected function sanitizeCurrencyInputs(Request $request, array $keys): void
    {
        $merged = [];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                $raw = $request->input($key);
                if (is_string($raw)) {
                    $cleaned = preg_replace('/[^\d]/', '', $raw);
                    $merged[$key] = ($cleaned === '' || $cleaned === null) ? null : (float) $cleaned;
                }
            }
        }

        if (! empty($merged)) {
            $request->merge($merged);
        }
    }

    /**
     * Normalize URL input strings (auto-prepend https:// if scheme is missing).
     *
     * @param  list<string>  $keys
     */
    protected function normalizeUrlInputs(Request $request, array $keys): void
    {
        $merged = [];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                $val = trim((string) $request->input($key));
                if ($val !== '' && ! preg_match('~^https?://~i', $val) && str_contains($val, '.')) {
                    $merged[$key] = 'https://'.$val;
                }
            }
        }

        if (! empty($merged)) {
            $request->merge($merged);
        }
    }
}
