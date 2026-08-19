<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Sucursal;
use App\Services\ContextoNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class ControladorReportes extends Controller
{
    public function productos(Request $request)
    {
        $this->authorize('reportes.ver');
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $desde = $startDate . ' 00:00:00';
        $hasta = $endDate . ' 23:59:59';

        $productos = DetalleVenta::whereHas('venta', function ($q) use ($desde, $hasta) {
                $q->whereBetween('created_at', [$desde, $hasta]);
            })
            ->select('producto_id', 'nombre_producto')
            ->selectRaw('SUM(cantidad) as total_vendido')
            ->selectRaw('SUM(subtotal) as total_ingreso')
            ->groupBy('producto_id', 'nombre_producto')
            ->orderByDesc('total_vendido')
            ->limit(20)
            ->get();

        $totalVentas = Venta::whereBetween('created_at', [$desde, $hasta])->count();
        $totalIngresos = (float) Venta::whereBetween('created_at', [$desde, $hasta])->sum('total');

        return view('dashboard.productos-reporte', compact('productos', 'startDate', 'endDate', 'totalVentas', 'totalIngresos'));
    }

    public function categorias(Request $request)
    {
        $this->authorize('reportes.ver');
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $desde = $startDate . ' 00:00:00';
        $hasta = $endDate . ' 23:59:59';

        $categorias = DetalleVenta::whereHas('venta', function ($q) use ($desde, $hasta) {
                $q->whereBetween('created_at', [$desde, $hasta]);
            })
            ->join('productos', 'detalles_venta.producto_id', '=', 'productos.id')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->select('categorias.nombre as categoria_nombre')
            ->selectRaw('SUM(detalles_venta.cantidad) as total_vendido')
            ->selectRaw('SUM(detalles_venta.subtotal) as total_ingreso')
            ->groupBy('categorias.nombre')
            ->orderByDesc('total_ingreso')
            ->get();

        return view('dashboard.categorias-reporte', compact('categorias', 'startDate', 'endDate'));
    }

    public function metodosPago(Request $request)
    {
        $this->authorize('reportes.ver');
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $desde = $startDate . ' 00:00:00';
        $hasta = $endDate . ' 23:59:59';

        $metodos = Venta::whereBetween('created_at', [$desde, $hasta])
            ->select('metodo_pago')
            ->selectRaw('COUNT(*) as total_ventas')
            ->selectRaw('SUM(total) as total_ingreso')
            ->selectRaw('AVG(total) as promedio_venta')
            ->groupBy('metodo_pago')
            ->orderByDesc('total_ingreso')
            ->get();

        $totalGeneral = (float) Venta::whereBetween('created_at', [$desde, $hasta])->sum('total');

        return view('dashboard.metodos-pago-reporte', compact('metodos', 'startDate', 'endDate', 'totalGeneral'));
    }

    public function tendencias(Request $request)
    {
        $this->authorize('reportes.ver');
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $desde = $startDate . ' 00:00:00';
        $hasta = $endDate . ' 23:59:59';

        $ventasActuales = Venta::whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('DATE(created_at) as fecha')
            ->selectRaw('COUNT(*) as total_ventas')
            ->selectRaw('SUM(total) as total_ingreso')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('fecha')
            ->get();

        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        $diffDays = $startCarbon->diffInDays($endCarbon);
        $prevStart = $startCarbon->copy()->subDays($diffDays + 1)->toDateString();
        $prevEnd = $startCarbon->copy()->subDay()->toDateString();

        $ventasAnteriores = Venta::whereBetween('created_at', [$prevStart . ' 00:00:00', $prevEnd . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as fecha')
            ->selectRaw('COUNT(*) as total_ventas')
            ->selectRaw('SUM(total) as total_ingreso')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('fecha')
            ->get();

        $totalActual = (float) $ventasActuales->sum('total_ingreso');
        $totalAnterior = (float) $ventasAnteriores->sum('total_ingreso');
        $variacion = $totalAnterior > 0 ? (($totalActual - $totalAnterior) / $totalAnterior) * 100 : 0;

        return view('dashboard.tendencias-reporte', compact(
            'ventasActuales', 'ventasAnteriores', 'startDate', 'endDate',
            'totalActual', 'totalAnterior', 'variacion', 'prevStart', 'prevEnd'
        ));
    }

    public function porSucursal(Request $request)
    {
        $this->authorize('reportes.ventas_o_cajeros');
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $desde = $startDate . ' 00:00:00';
        $hasta = $endDate . ' 23:59:59';

        $sucursales = Venta::whereBetween('ventas.created_at', [$desde, $hasta])
            ->leftJoin('sucursales', 'ventas.sucursal_id', '=', 'sucursales.id')
            ->selectRaw('COALESCE(sucursales.nombre, "Sin sucursal") as sucursal_nombre')
            ->selectRaw('COUNT(*) as total_ventas')
            ->selectRaw('SUM(total) as total_ingreso')
            ->selectRaw('AVG(total) as promedio_venta')
            ->groupByRaw('COALESCE(sucursales.nombre, "Sin sucursal")')
            ->orderByDesc('total_ingreso')
            ->get();

        $cajeros = Venta::whereBetween('ventas.created_at', [$desde, $hasta])
            ->leftJoin('usuarios', 'ventas.usuario_id', '=', 'usuarios.id')
            ->selectRaw('COALESCE(usuarios.nombre, "Sin usuario") as cajero_nombre')
            ->selectRaw('COUNT(*) as total_ventas')
            ->selectRaw('SUM(total) as total_ingreso')
            ->selectRaw('AVG(total) as promedio_venta')
            ->groupByRaw('COALESCE(usuarios.nombre, "Sin usuario")')
            ->orderByDesc('total_ingreso')
            ->get();

        return view('dashboard.sucursal-reporte', compact('sucursales', 'cajeros', 'startDate', 'endDate'));
    }

    public function exportarVentasCsv(Request $request)
    {
        $this->authorize('reportes.ver');
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $desde = $startDate . ' 00:00:00';
        $hasta = $endDate . ' 23:59:59';

        $ventas = Venta::whereBetween('created_at', [$desde, $hasta])
            ->with(['detalles', 'usuario', 'sucursal'])
            ->get();

        $filename = "ventas_{$startDate}_{$endDate}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($ventas) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Fecha', 'Comprobante', 'Metodo Pago', 'Subtotal', 'Descuento', 'Impuesto', 'Total', 'Pagado', 'Cambio', 'Cajero', 'Sucursal']);

            foreach ($ventas as $venta) {
                fputcsv($file, [
                    $venta->created_at->format('Y-m-d H:i'),
                    $venta->numero_comprobante,
                    $venta->metodo_pago,
                    number_format($venta->subtotal, 2),
                    number_format($venta->descuento, 2),
                    number_format($venta->impuesto, 2),
                    number_format($venta->total, 2),
                    number_format($venta->pagado, 2),
                    number_format($venta->cambio, 2),
                    $venta->usuario->nombre ?? 'N/A',
                    $venta->sucursal->nombre ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
