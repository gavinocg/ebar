<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControladorCategorias as CategoryController;
use App\Http\Controllers\ControladorProductos as ProductController;
use App\Http\Controllers\ControladorVentas as SaleController;
use App\Http\Controllers\ControladorPuntoVenta as PosController;
use App\Http\Controllers\ControladorPanel as DashboardController;
use App\Http\Controllers\ControladorImpresoras as PrinterController;
use App\Http\Controllers\ControladorConfiguracionNegocio as BusinessSettingController;
use App\Http\Controllers\ControladorAutenticacion as AuthController;
use App\Http\Controllers\ControladorCaja;

Route::get('/inicio-sesion', [AuthController::class, 'create'])->name('inicio_sesion');
Route::post('/inicio-sesion', [AuthController::class, 'store'])->name('inicio_sesion.guardar');
Route::post('/cerrar-sesion', [AuthController::class, 'destroy'])->middleware('auth')->name('cerrar_sesion');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('punto_venta.inicio');
    });

    Route::resource('categorias', CategoryController::class)->parameters(['categorias' => 'category'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('productos', ProductController::class)->parameters(['productos' => 'product'])->except(['show']);
    Route::resource('ventas', SaleController::class)->parameters(['ventas' => 'sale'])->only(['index', 'show']);
    Route::resource('impresoras', PrinterController::class)->parameters(['impresoras' => 'printer'])->only(['index', 'store', 'update', 'destroy']);
    Route::post('impresoras/{printer}/probar', [PrinterController::class, 'probar'])->name('impresoras.probar');

    Route::get('/punto-venta', [PosController::class, 'index'])->name('punto_venta.inicio');
    Route::get('/punto-venta/buscar', [PosController::class, 'buscar'])->name('punto_venta.buscar');
    Route::post('/punto-venta/cobrar', [PosController::class, 'cobrar'])->name('punto_venta.cobrar');
    Route::post('/caja/abrir', [ControladorCaja::class, 'abrir'])->name('caja.abrir');
    Route::post('/caja/cerrar', [ControladorCaja::class, 'cerrar'])->name('caja.cerrar');

    Route::get('/panel', [DashboardController::class, 'index'])->name('panel.inicio');
    Route::get('/reportes/ventas', [DashboardController::class, 'reporteVentas'])->name('reportes.ventas');
    Route::get('/reportes/inventario', [DashboardController::class, 'reporteInventario'])->name('reportes.inventario');

    Route::get('/configuracion/negocio', [BusinessSettingController::class, 'index'])->name('configuracion.negocio');
    Route::post('/configuracion/negocio', [BusinessSettingController::class, 'update'])->name('configuracion.negocio.actualizar');
});
