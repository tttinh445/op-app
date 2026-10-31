<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GoogleSheetConfigController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// Redirect root to Customers page
Route::get('/', function () {
    return redirect()->route('customers.index');
})->name('home');

// Khách hàng (Customers)
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/check-code', [CustomerController::class, 'checkCode'])->name('check_code');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::post('/sync', [CustomerController::class, 'sync'])->name('sync');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
});

// Nhà cung cấp (Suppliers)
Route::prefix('suppliers')->name('suppliers.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/search', [SupplierController::class, 'search'])->name('search');
    Route::get('/check-code', [SupplierController::class, 'checkCode'])->name('check_code');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::post('/sync', [SupplierController::class, 'sync'])->name('sync');
    Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
    Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
    Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
});

// Sản phẩm (Products)
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/check-code', [ProductController::class, 'checkCode'])->name('check_code');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::post('/sync', [ProductController::class, 'sync'])->name('sync');
    Route::get('/{product}', [ProductController::class, 'show'])->name('show');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
});

// Cấu hình Google Sheets (Google Sheet Configs)
Route::prefix('sheets')->name('sheets.')->group(function () {
    Route::get('/', [GoogleSheetConfigController::class, 'index'])->name('index');
    Route::post('/', [GoogleSheetConfigController::class, 'store'])->name('store');
    Route::get('/export', [GoogleSheetConfigController::class, 'exportCsv'])->name('export');
    Route::get('/{sheetConfig}', [GoogleSheetConfigController::class, 'show'])->name('show');
    Route::put('/{sheetConfig}', [GoogleSheetConfigController::class, 'update'])->name('update');
    Route::delete('/{sheetConfig}', [GoogleSheetConfigController::class, 'destroy'])->name('destroy');
    Route::post('/{sheetConfig}/sync', [GoogleSheetConfigController::class, 'sync'])->name('sync');
});
