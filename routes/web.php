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
use App\Http\Controllers\ControladorCajas;
use App\Http\Controllers\ControladorClientes;
use App\Http\Controllers\ControladorPlataforma;
use App\Http\Controllers\ControladorNegocios;
use App\Http\Controllers\ControladorMembresias;
use App\Http\Controllers\ControladorSeleccionNegocio;
use App\Http\Controllers\ControladorSucursales;
use App\Http\Controllers\ControladorCajeros;
use App\Http\Controllers\ControladorInventario;
use App\Http\Controllers\ControladorCompras;
use App\Http\Controllers\ControladorConteos;
use App\Http\Controllers\ControladorEtiquetas;
use App\Http\Controllers\ControladorAuditorias;
use App\Http\Controllers\ControladorReembolsos;

Route::get('/inicio-sesion', [AuthController::class, 'create'])->name('inicio_sesion');
Route::post('/inicio-sesion', [AuthController::class, 'store'])->name('inicio_sesion.guardar');
Route::get('/inicio-sesion/cajero', [AuthController::class, 'cajero'])->name('inicio_sesion.cajero');
Route::post('/inicio-sesion/cajero', [AuthController::class, 'cajeroBuscar'])->name('inicio_sesion.cajero.buscar');
Route::get('/inicio-sesion/pin', [AuthController::class, 'pin'])->name('inicio_sesion.pin');
Route::post('/inicio-sesion/pin', [AuthController::class, 'pinValidar'])->name('inicio_sesion.pin.validar');
Route::post('/cerrar-sesion', [AuthController::class, 'destroy'])->middleware('auth')->name('cerrar_sesion');

Route::middleware(['auth'])->group(function () {
    Route::get('/seleccionar-negocio', [ControladorSeleccionNegocio::class, 'mostrar'])->name('negocio.seleccionar');
    Route::post('/seleccionar-negocio', [ControladorSeleccionNegocio::class, 'guardar'])->name('negocio.seleccionar.guardar');
    Route::get('/negocio/cambiar', [ControladorSeleccionNegocio::class, 'cambiar'])->name('negocio.cambiar');
    Route::post('/negocio/cambiar-sucursal', [ControladorSeleccionNegocio::class, 'cambiarSucursal'])->name('negocio.sucursal.cambiar');
});

Route::middleware(['auth', 'super_admin'])->prefix('plataforma')->name('plataforma.')->group(function () {
    Route::get('/', [ControladorPlataforma::class, 'index'])->name('inicio');
    Route::get('/negocios', [ControladorNegocios::class, 'index'])->name('negocios.index');
    Route::get('/negocios/crear', [ControladorNegocios::class, 'create'])->name('negocios.create');
    Route::post('/negocios', [ControladorNegocios::class, 'store'])->name('negocios.store');
    Route::get('/negocios/{negocio}/editar', [ControladorNegocios::class, 'edit'])->name('negocios.edit');
    Route::put('/negocios/{negocio}', [ControladorNegocios::class, 'update'])->name('negocios.update');
    Route::delete('/negocios/{negocio}', [ControladorNegocios::class, 'destroy'])->name('negocios.destroy');
    Route::get('/negocios/{negocio}/membresia/renovar', [ControladorMembresias::class, 'renovar'])->name('negocios.membresia.renovar');
    Route::post('/negocios/{negocio}/membresia/suspender', [ControladorMembresias::class, 'suspender'])->name('negocios.membresia.suspender');
    Route::post('/negocios/{negocio}/membresia/reactivar', [ControladorMembresias::class, 'reactivar'])->name('negocios.membresia.reactivar');
});

Route::middleware(['auth', 'negocio'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('punto_venta.inicio');
    });

    Route::get('/punto-venta', [PosController::class, 'index'])->name('punto_venta.inicio');
    Route::get('/punto-venta/buscar', [PosController::class, 'buscar'])->name('punto_venta.buscar');
    Route::post('/punto-venta/desbloquear', [PosController::class, 'desbloquear'])->name('punto_venta.desbloquear');
    Route::post('/punto-venta/bloquear', [PosController::class, 'bloquear'])->name('punto_venta.bloquear');
    Route::post('/punto-venta/cobrar', [PosController::class, 'cobrar'])->name('punto_venta.cobrar');
    Route::get('/clientes/buscar', [ControladorClientes::class, 'buscar'])->name('clientes.buscar');
    Route::post('/caja/abrir', [ControladorCaja::class, 'abrir'])->name('caja.abrir');
    Route::get('/caja/cerrar', [ControladorCaja::class, 'cerrarForm'])->name('caja.cerrar.form');
    Route::post('/caja/cerrar', [ControladorCaja::class, 'cerrar'])->name('caja.cerrar');
    Route::post('/caja/movimiento', [ControladorCaja::class, 'movimiento'])->name('caja.movimiento');
    Route::post('/caja/{turnoCaja}/reabrir', [ControladorCaja::class, 'reabrir'])
        ->middleware('rol_negocio:propietario')->name('caja.reabrir');
    Route::get('/caja/reporte', [ControladorCaja::class, 'reporte'])
        ->middleware('rol_negocio:admin_bar')->name('caja.reporte');
    Route::get('/caja/turnos/{turnoCaja}', [ControladorCaja::class, 'turnoDetalle'])
        ->middleware('rol_negocio:admin_bar')->name('caja.turno-detalle');

    Route::middleware('rol_negocio:admin_bar')->group(function () {
        Route::resource('categorias', CategoryController::class)->parameters(['categorias' => 'category'])->only(['index', 'store', 'update', 'destroy']);
        Route::resource('productos', ProductController::class)->parameters(['productos' => 'product'])->except(['show']);
        Route::resource('ventas', SaleController::class)->parameters(['ventas' => 'sale'])->only(['index', 'show']);
        Route::resource('impresoras', PrinterController::class)->parameters(['impresoras' => 'printer'])->only(['index', 'store', 'update', 'destroy'])
            ->middleware('rol_negocio:propietario');
        Route::post('impresoras/{printer}/probar', [PrinterController::class, 'probar'])->name('impresoras.probar')
            ->middleware('rol_negocio:propietario');
        Route::resource('sucursales', ControladorSucursales::class)->parameters(['sucursales' => 'sucursal'])->only(['index', 'store', 'update', 'destroy'])
            ->middleware('rol_negocio:propietario');
        Route::resource('cajas', ControladorCajas::class)->parameters(['cajas' => 'caja'])->only(['index', 'store', 'update', 'destroy'])
            ->middleware('rol_negocio:propietario');
        Route::resource('cajeros', ControladorCajeros::class)->parameters(['cajeros' => 'cajero'])->only(['index', 'store', 'update', 'destroy'])
            ->middleware('rol_negocio:propietario');
        Route::get('/inventario/historial', [ControladorInventario::class, 'historial'])->name('inventario.historial');
        Route::post('/inventario/ajustar', [ControladorInventario::class, 'ajustar'])->name('inventario.ajustar');

        Route::get('/proveedores', [ControladorCompras::class, 'indexProveedores'])->name('proveedores.index');
        Route::post('/proveedores', [ControladorCompras::class, 'storeProveedor'])->name('proveedores.store');
        Route::put('/proveedores/{proveedor}', [ControladorCompras::class, 'updateProveedor'])->name('proveedores.update');
        Route::delete('/proveedores/{proveedor}', [ControladorCompras::class, 'destroyProveedor'])->name('proveedores.destroy');
        Route::get('/compras/ordenes', [ControladorCompras::class, 'ordenes'])->name('ordenes.index');
        Route::post('/compras/ordenes', [ControladorCompras::class, 'storeOrden'])->name('ordenes.store');
        Route::post('/compras/ordenes/{ordenCompra}/recibir', [ControladorCompras::class, 'recibir'])->name('ordenes.recibir');
        Route::delete('/compras/ordenes/{orden}', [ControladorCompras::class, 'destroyOrden'])->name('ordenes.destroy');

        Route::get('/inventario/conteos', [ControladorConteos::class, 'index'])->name('conteos.index');
        Route::get('/inventario/conteos/crear', [ControladorConteos::class, 'crear'])->name('conteos.crear');
        Route::post('/inventario/conteos', [ControladorConteos::class, 'store'])->name('conteos.store');
        Route::post('/inventario/conteos/{conteo}/aplicar', [ControladorConteos::class, 'aplicar'])->name('conteos.aplicar');

        Route::get('/productos/exportar', [ProductController::class, 'exportar'])->name('productos.exportar');
        Route::post('/productos/importar', [ProductController::class, 'importar'])->name('productos.importar');
        Route::get('/productos/etiquetas', [ControladorEtiquetas::class, 'index'])->name('etiquetas.index');
        Route::post('/productos/etiquetas/imprimir', [ControladorEtiquetas::class, 'imprimir'])->name('etiquetas.imprimir');
        Route::get('/auditorias', [ControladorAuditorias::class, 'index'])->name('auditorias.index')
            ->middleware('rol_negocio:propietario');
        Route::get('/reembolsos', [ControladorReembolsos::class, 'index'])->name('reembolsos.index');
        Route::post('/ventas/{venta}/reembolsar', [ControladorReembolsos::class, 'crear'])->name('reembolsos.crear');

        Route::get('/panel', [DashboardController::class, 'index'])->name('panel.inicio');
        Route::get('/reportes/ventas', [DashboardController::class, 'reporteVentas'])->name('reportes.ventas')
            ->middleware('rol_negocio:propietario');
        Route::get('/reportes/inventario', [DashboardController::class, 'reporteInventario'])->name('reportes.inventario')
            ->middleware('rol_negocio:propietario');
        Route::get('/reportes/cajeros', [DashboardController::class, 'reportePorCajero'])->name('reportes.cajeros')
            ->middleware('rol_negocio:propietario');

        Route::get('/configuracion/negocio', [BusinessSettingController::class, 'index'])->name('configuracion.negocio')
            ->middleware('rol_negocio:propietario');
        Route::post('/configuracion/negocio', [BusinessSettingController::class, 'update'])->name('configuracion.negocio.actualizar')
            ->middleware('rol_negocio:propietario');
    });
});
