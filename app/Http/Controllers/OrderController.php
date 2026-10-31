<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\GoogleSheetConfig;
use App\Models\Order;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with server-side pagination (30 items/page).
     * Default sort: order_date desc.
     */
    public function index(Request $request, GoogleSheetService $sheetService): View|JsonResponse
    {
        $query = Order::query()->with(['customerRecord', 'googleSheetConfig']);

        // Filter: Mã
        if ($code = trim((string) $request->input('code', ''))) {
            $query->where('code', 'like', "%{$code}%");
        }

        // Filter: Khách hàng (mã hoặc tên)
        if ($customer = trim((string) $request->input('customer', ''))) {
            $query->where(function ($q) use ($customer) {
                $q->where('customer', 'like', "%{$customer}%")
                    ->orWhereHas('customerRecord', function ($sq) use ($customer) {
                        $sq->where('name', 'like', "%{$customer}%")
                            ->orWhere('code', 'like', "%{$customer}%");
                    });
            });
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
        $sortBy = $request->input('sort_by', 'order_date');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc'));

        $allowedSorts = [
            'order_date',
            'code',
            'customer',
            'total_amount',
            'status',
            'salesperson',
            'sale_payment_status',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'order_date';
        }
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $orders = $query->orderBy($sortBy, $sortDir)->paginate(30)->withQueryString();

        // Load all employees without pagination for select filter & modals
        $employees = Employee::query()->orderBy('name', 'asc')->get(['id', 'code', 'name']);

        // Google Sheet configs and pending changes for Orders
        $sheetConfigs = GoogleSheetConfig::where('sheet_type', 'order')->where('is_active', true)->get();
        $pendingChanges = $sheetService->getPendingChangesSummary('order');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $orders,
                'pending_changes' => $pendingChanges,
            ]);
        }

        return view('orders.index', [
            'orders' => $orders,
            'employees' => $employees,
            'sheetConfigs' => $sheetConfigs,
            'pendingChanges' => $pendingChanges,
            'currentCode' => $code,
            'currentCustomer' => $customer,
            'currentSalesperson' => $salesperson,
            'currentSalePaymentStatus' => $salePaymentStatus,
            'currentSortBy' => $sortBy,
            'currentSortDir' => $sortDir,
            'orderTypes' => Order::TYPES,
            'orderStatuses' => Order::STATUSES,
            'salePaymentStatuses' => Order::SALE_PAYMENT_STATUSES,
            'activeTab' => 'orders',
        ]);
    }

    /**
     * Check if order code already exists (for blur validation).
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $ignoreId = $request->input('ignore_id');

        if ($code === '') {
            return response()->json(['exists' => false]);
        }

        $query = Order::where('code', $code);
        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Mã đơn hàng này đã tồn tại trên hệ thống.' : 'Mã hợp lệ.',
        ]);
    }

    /**
     * Store a newly created order and sync to Google Sheet.
     */
    public function store(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $this->sanitizeCurrencyInputs($request, ['total_amount', 'sale_discount']);
        $this->normalizeUrlInputs($request, ['quote_url']);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:orders,code'],
            'order_date' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:255'],
            'customer' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Order::TYPES)],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Order::STATUSES)],
            'quote_url' => ['nullable', 'url', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'salesperson' => ['nullable', 'string', 'max:255'],
            'sale_discount' => ['nullable', 'numeric', 'min:0'],
            'sale_payment_status' => ['required', Rule::in(Order::SALE_PAYMENT_STATUSES)],
        ], [
            'code.required' => 'Vui lòng nhập mã đơn hàng.',
            'code.unique' => 'Mã đơn hàng này đã tồn tại.',
            'order_date.required' => 'Vui lòng chọn ngày đơn hàng.',
            'customer.required' => 'Vui lòng nhập hoặc chọn khách hàng.',
            'quote_url.url' => 'Link báo giá phải là định dạng URL hợp lệ.',
        ]);

        $totalAmount = isset($validated['total_amount']) ? (float) $validated['total_amount'] : 0;
        $saleDiscount = $validated['sale_discount'] ?? null;
        if ($saleDiscount === null && $totalAmount > 0) {
            $saleDiscount = Order::calculateSaleDiscount($totalAmount);
        }

        $order = Order::create([
            'code' => trim($validated['code']),
            'order_date' => $validated['order_date'],
            'name' => isset($validated['name']) && trim((string) $validated['name']) !== '' ? trim((string) $validated['name']) : null,
            'customer' => trim($validated['customer']),
            'type' => $validated['type'] ?? 'Đơn hàng',
            'total_amount' => $totalAmount,
            'status' => $validated['status'] ?? 'Mới',
            'quote_url' => isset($validated['quote_url']) && trim((string) $validated['quote_url']) !== '' ? trim((string) $validated['quote_url']) : null,
            'note' => isset($validated['note']) && trim((string) $validated['note']) !== '' ? trim((string) $validated['note']) : null,
            'salesperson' => isset($validated['salesperson']) && trim((string) $validated['salesperson']) !== '' ? trim((string) $validated['salesperson']) : null,
            'sale_discount' => $saleDiscount,
            'sale_payment_status' => $validated['sale_payment_status'] ?? 'Chưa thanh toán',
            'sync_status' => 'pending_create',
        ]);

        // Auto sync addition to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($order, 'append');

        $message = "Thêm đơn hàng {$order->code} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Đã đồng bộ lên Google Sheet)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $order,
                'sheet_sync' => $syncResult,
            ], 201);
        }

        return redirect()->route('orders.index')->with('success', $message);
    }

    /**
     * Display the specified order in JSON format.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['customerRecord', 'googleSheetConfig']);

        return response()->json([
            'success' => true,
            'data' => array_merge($order->toArray(), [
                'order_date_formatted' => $order->order_date ? $order->order_date->format('Y-m-d\TH:i') : '',
                'customer_display' => $order->customer_display_name,
                'total_amount_formatted' => $order->total_amount !== null ? number_format((float) $order->total_amount, 0, ',', '.') : '0',
                'sale_discount_formatted' => $order->sale_discount !== null ? number_format((float) $order->sale_discount, 0, ',', '.') : '',
            ]),
        ]);
    }

    /**
     * Update the specified order and sync to Google Sheet.
     */
    public function update(Request $request, Order $order, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $this->sanitizeCurrencyInputs($request, ['total_amount', 'sale_discount']);
        $this->normalizeUrlInputs($request, ['quote_url']);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('orders', 'code')->ignore($order->id)],
            'order_date' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:255'],
            'customer' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Order::TYPES)],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Order::STATUSES)],
            'quote_url' => ['nullable', 'url', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'salesperson' => ['nullable', 'string', 'max:255'],
            'sale_discount' => ['nullable', 'numeric', 'min:0'],
            'sale_payment_status' => ['required', Rule::in(Order::SALE_PAYMENT_STATUSES)],
        ], [
            'code.required' => 'Vui lòng nhập mã đơn hàng.',
            'code.unique' => 'Mã đơn hàng này đã tồn tại.',
            'order_date.required' => 'Vui lòng chọn ngày đơn hàng.',
            'customer.required' => 'Vui lòng nhập hoặc chọn khách hàng.',
            'quote_url.url' => 'Link báo giá phải là định dạng URL hợp lệ.',
        ]);

        $originalCode = $order->code;
        $newStatus = $order->sync_status === 'pending_create' ? 'pending_create' : 'pending_update';

        $totalAmount = isset($validated['total_amount']) ? (float) $validated['total_amount'] : 0;
        $saleDiscount = $validated['sale_discount'] ?? null;
        if ($saleDiscount === null && $totalAmount > 0) {
            $saleDiscount = Order::calculateSaleDiscount($totalAmount);
        }

        $order->update([
            'code' => trim($validated['code']),
            'order_date' => $validated['order_date'],
            'name' => isset($validated['name']) && trim((string) $validated['name']) !== '' ? trim((string) $validated['name']) : null,
            'customer' => trim($validated['customer']),
            'type' => $validated['type'] ?? 'Đơn hàng',
            'total_amount' => $validated['total_amount'] ?? 0,
            'status' => $validated['status'] ?? 'Mới',
            'quote_url' => isset($validated['quote_url']) && trim((string) $validated['quote_url']) !== '' ? trim((string) $validated['quote_url']) : null,
            'note' => isset($validated['note']) && trim((string) $validated['note']) !== '' ? trim((string) $validated['note']) : null,
            'salesperson' => isset($validated['salesperson']) && trim((string) $validated['salesperson']) !== '' ? trim((string) $validated['salesperson']) : null,
            'sale_discount' => $validated['sale_discount'] ?? null,
            'sale_payment_status' => $validated['sale_payment_status'] ?? 'Chưa thanh toán',
            'sync_status' => $newStatus,
        ]);

        // Auto sync update to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($order, 'update', $originalCode);

        $message = "Cập nhật đơn hàng {$order->code} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Đã đồng bộ lên Google Sheet)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $order,
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('orders.index')->with('success', $message);
    }

    /**
     * Remove the specified order and sync deletion to Google Sheet.
     */
    public function destroy(Request $request, Order $order, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $code = $order->code;
        $name = $order->name ?: $order->code;
        $configId = $order->google_sheet_config_id;

        // Record pending delete
        $pendingDelete = $sheetService->recordPendingDelete('order', $code, $name, $configId);

        $order->delete();

        // Auto sync delete to Google Sheet
        $syncResult = $sheetService->syncDeleteToSheet('order', $code, $configId);
        if ($syncResult['synced']) {
            $pendingDelete->update(['status' => 'synced']);
        }

        $message = "Đã xoá đơn hàng {$code} thành công!";
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

        return redirect()->route('orders.index')->with('success', $message);
    }

    /**
     * Trigger Google Sheet sync for orders (pull, push marked, or push all).
     */
    public function sync(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $configId = $request->input('config_id');
        $config = $configId
            ? GoogleSheetConfig::find($configId)
            : GoogleSheetConfig::where('sheet_type', 'order')->where('is_active', true)->first();

        if (! $config) {
            return redirect()->route('orders.index')->with('error', 'Chưa có cấu hình Google Sheet cho Đơn hàng. Vui lòng thêm liên kết Google Sheet trước.');
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

            return redirect()->route('orders.index')->with(
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

            return redirect()->route('orders.index')->with('error', 'Lỗi đồng bộ: '.$e->getMessage());
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
