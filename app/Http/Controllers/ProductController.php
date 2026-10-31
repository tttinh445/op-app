<?php

namespace App\Http\Controllers;

use App\Models\GoogleSheetConfig;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\GoogleSheetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request, GoogleSheetService $sheetService): View|JsonResponse
    {
        $query = Product::query()->with(['googleSheetConfig', 'supplier']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('category_code', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('other_name', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $currentSupplierDisplay = '';
        if ($supplierCode = $request->input('supplier_code')) {
            $query->where('supplier_code', $supplierCode);
            $sup = Supplier::where('code', $supplierCode)->first(['name', 'code']);
            $currentSupplierDisplay = $sup ? "{$sup->name} ({$sup->code})" : $supplierCode;
        }

        if ($unit = $request->input('unit')) {
            $query->where('unit', $unit);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['code', 'name', 'category_code', 'unit', 'supplier_code', 'brand', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        if (! in_array(strtolower($sortDir), ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $products = $query->orderBy($sortBy, $sortDir)->paginate(15)->withQueryString();
        $sheetConfigs = GoogleSheetConfig::where('sheet_type', 'product')->where('is_active', true)->get();
        $pendingChanges = $sheetService->getPendingChangesSummary('product');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products,
                'pending_changes' => $pendingChanges,
            ]);
        }

        return view('products.index', [
            'products' => $products,
            'sheetConfigs' => $sheetConfigs,
            'pendingChanges' => $pendingChanges,
            'currentSearch' => $search,
            'currentSupplier' => $supplierCode,
            'currentSupplierDisplay' => $currentSupplierDisplay,
            'currentUnit' => $unit,
            'currentSortBy' => $sortBy,
            'currentSortDir' => $sortDir,
        ]);
    }

    /**
     * Check if product code already exists (for blur validation).
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $ignoreId = $request->input('ignore_id');

        if ($code === '') {
            return response()->json(['exists' => false]);
        }

        $query = Product::where('code', $code);
        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Mã sản phẩm này đã tồn tại trên hệ thống.' : 'Mã hợp lệ.',
        ]);
    }

    /**
     * Store a newly created product and sync to Google Sheet.
     */
    public function store(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'category_code' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'supplier_code' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:255'],
            'other_name' => ['nullable', 'string', 'max:255'],
        ], [
            'code.required' => 'Vui lòng nhập Mã sản phẩm.',
            'code.unique' => 'Mã sản phẩm này đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên sản phẩm.',
            'unit.required' => 'Vui lòng chọn hoặc nhập ĐVT.',
        ]);

        $validated['sync_status'] = 'pending_create';
        $product = Product::create($validated);

        // Auto sync append to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($product, 'append');
        if ($syncResult['synced']) {
            $product->updateQuietly(['sync_status' => 'synced', 'synced_at' => now()]);
        }

        $message = "Đã thêm mới sản phẩm {$product->name} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Dữ liệu đã được thêm lên Google Sheet)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $product->load('supplier'),
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    /**
     * Return product details for modal edit.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $product->load('supplier'),
        ]);
    }

    /**
     * Update the specified product and sync to Google Sheet.
     */
    public function update(Request $request, Product $product, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('products', 'code')->ignore($product->id)],
            'category_code' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'supplier_code' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:255'],
            'other_name' => ['nullable', 'string', 'max:255'],
        ], [
            'code.required' => 'Vui lòng nhập Mã sản phẩm.',
            'code.unique' => 'Mã sản phẩm này đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên sản phẩm.',
            'unit.required' => 'Vui lòng chọn hoặc nhập ĐVT.',
        ]);

        $originalCode = $product->code;
        $newStatus = $product->sync_status === 'pending_create' ? 'pending_create' : 'pending_update';
        $validated['sync_status'] = $newStatus;
        $product->update($validated);

        // Auto sync update to Google Sheet
        $syncResult = $sheetService->syncModelToSheet($product, 'update', $originalCode);
        if ($syncResult['synced']) {
            $product->updateQuietly(['sync_status' => 'synced', 'synced_at' => now()]);
        }

        $message = "Đã cập nhật sản phẩm {$product->name} thành công!";
        if ($syncResult['synced']) {
            $message .= ' (Google Sheet đã được cập nhật)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $product->load('supplier'),
                'sheet_sync' => $syncResult,
            ]);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    /**
     * Remove the specified product and sync deletion to Google Sheet.
     */
    public function destroy(Request $request, Product $product, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $name = $product->name;
        $code = $product->code;
        $configId = $product->google_sheet_config_id;

        // Record pending delete
        $pendingDelete = $sheetService->recordPendingDelete('product', $code, $name, $configId);

        $product->delete();

        // Auto sync delete to Google Sheet
        $syncResult = $sheetService->syncDeleteToSheet('product', $code, $configId);
        if ($syncResult['synced']) {
            $pendingDelete->update(['status' => 'synced']);
        }

        $message = "Đã xoá sản phẩm {$name} thành công!";
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

        return redirect()->route('products.index')->with('success', $message);
    }

    /**
     * Sync products from Google Sheets (pull, push marked, or push all).
     */
    public function sync(Request $request, GoogleSheetService $sheetService): RedirectResponse|JsonResponse
    {
        $configId = $request->input('config_id');
        $config = $configId
            ? GoogleSheetConfig::find($configId)
            : GoogleSheetConfig::where('sheet_type', 'product')->where('is_active', true)->first();

        if (! $config) {
            return redirect()->route('products.index')->with('error', 'Chưa có cấu hình Google Sheet cho Sản phẩm.');
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
                return redirect()->route('products.index')->with('error', $result['message']);
            }

            return redirect()->route('products.index')->with('success', $result['message']);
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->route('products.index')->with('error', 'Lỗi đồng bộ: '.$e->getMessage());
        }
    }
}
