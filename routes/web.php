<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\BusinessSettingController;

Route::get('/', function () {
    return redirect()->route('pos.index');
});

Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
Route::resource('sales', SaleController::class)->only(['index', 'show']);
Route::resource('printers', PrinterController::class);
Route::post('printers/{printer}/test', [PrinterController::class, 'test'])->name('printers.test');

Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/reports/sales', [DashboardController::class, 'salesReport'])->name('reports.sales');
Route::get('/reports/inventory', [DashboardController::class, 'inventoryReport'])->name('reports.inventory');

Route::get('/settings/business', [BusinessSettingController::class, 'index'])->name('settings.business');
Route::post('/settings/business', [BusinessSettingController::class, 'update'])->name('settings.business.update');
