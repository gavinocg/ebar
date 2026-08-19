<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Sucursal;
use App\Models\ConfiguracionNegocio;
use App\Services\ContextoNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function impuestos(Request $request)
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

        $config = ConfiguracionNegocio::obtenerConfiguracion();
        $porcentajeImpuesto = $config?->porcentaje_impuesto ?? 0;

        $ventas = Venta::whereBetween('created_at', [$desde, $hasta])
            ->with(['detalles'])
            ->get();

        $totalVentas = $ventas->count();
        $totalSubtotal = $ventas->sum('subtotal');
        $totalDescuento = $ventas->sum('descuento');
        $totalImpuesto = $ventas->sum('impuesto');
        $totalTotal = $ventas->sum('total');

        $baseImponible = $totalSubtotal - $totalDescuento;
        $impuestoCalculado = round($baseImponible * ($porcentajeImpuesto / 100), 2);

        $porMetodo = $ventas->groupBy('metodo_pago')->map(function ($grupo) use ($porcentajeImpuesto) {
            $subtotal = $grupo->sum('subtotal');
            $descuento = $grupo->sum('descuento');
            $impuesto = $grupo->sum('impuesto');
            $total = $grupo->sum('total');
            $base = $subtotal - $descuento;
            $calc = round($base * ($porcentajeImpuesto / 100), 2);
            return [
                'metodo' => $grupo->first()->metodo_pago,
                'ventas' => $grupo->count(),
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'base_imponible' => $base,
                'impuesto' => $impuesto,
                'impuesto_calculado' => $calc,
                'total' => $total,
            ];
        })->values();

        $detalle = DetalleVenta::whereHas('venta', function ($q) use ($desde, $hasta) {
                $q->whereBetween('created_at', [$desde, $hasta]);
            })
            ->join('productos', 'detalles_venta.producto_id', '=', 'productos.id')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->select('categorias.nombre as categoria_nombre')
            ->selectRaw('SUM(detalles_venta.cantidad) as total_vendido')
            ->selectRaw('SUM(detalles_venta.subtotal) as total_subtotal')
            ->selectRaw('SUM(detalles_venta.descuento) as total_descuento')
            ->selectRaw('SUM(detalles_venta.impuesto) as total_impuesto')
            ->groupBy('categorias.nombre')
            ->orderByDesc('total_subtotal')
            ->get();

        return view('dashboard.impuestos-reporte', compact(
            'startDate', 'endDate', 'porcentajeImpuesto',
            'totalVentas', 'totalSubtotal', 'totalDescuento', 'totalImpuesto', 'totalTotal',
            'baseImponible', 'impuestoCalculado',
            'porMetodo', 'detalle'
        ));
    }

    public function exportarXlsx(Request $request, string $tipo)
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

        $filename = "{$tipo}_{$startDate}_{$endDate}.xlsx";

        return Excel::download(new class($tipo, $desde, $hasta) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping {
            private string $tipo;
            private string $desde;
            private string $hasta;

            public function __construct(string $tipo, string $desde, string $hasta)
            {
                $this->tipo = $tipo;
                $this->desde = $desde;
                $this->hasta = $hasta;
            }

            public function collection()
            {
                return match ($this->tipo) {
                    'ventas' => $this->getVentas(),
                    'productos' => $this->getProductos(),
                    'categorias' => $this->getCategorias(),
                    'metodos_pago' => $this->getMetodosPago(),
                    'tendencias' => $this->getTendencias(),
                    'sucursal' => $this->getSucursal(),
                    'impuestos' => $this->getImpuestos(),
                    default => collect(),
                };
            }

            public function headings(): array
            {
                return match ($this->tipo) {
                    'ventas' => ['Fecha', 'Comprobante', 'Metodo Pago', 'Subtotal', 'Descuento', 'Impuesto', 'Total', 'Pagado', 'Cambio', 'Cajero', 'Sucursal'],
                    'productos' => ['Producto', 'Categoria', 'Total Vendido', 'Total Ingreso'],
                    'categorias' => ['Categoria', 'Total Vendido', 'Total Ingreso'],
                    'metodos_pago' => ['Metodo Pago', 'Total Ventas', 'Total Ingreso', 'Promedio Venta'],
                    'tendencias' => ['Fecha', 'Ventas', 'Ingreso Total'],
                    'sucursal' => ['Sucursal', 'Total Ventas', 'Total Ingreso', 'Promedio Venta'],
                    'impuestos' => ['Metodo Pago', 'Ventas', 'Subtotal', 'Descuento', 'Base Imponible', 'Impuesto', 'Impuesto Calculado', 'Total'],
                    default => [],
                };
            }

            public function map($row): array
            {
                return match ($this->tipo) {
                    'ventas' => [
                        $row['fecha'], $row['comprobante'], $row['metodo_pago'],
                        $row['subtotal'], $row['descuento'], $row['impuesto'],
                        $row['total'], $row['pagado'], $row['cambio'],
                        $row['cajero'], $row['sucursal'],
                    ],
                    'productos' => [$row['nombre'], $row['categoria'], $row['total_vendido'], $row['total_ingreso']],
                    'categorias' => [$row['categoria_nombre'], $row['total_vendido'], $row['total_ingreso']],
                    'metodos_pago' => [$row['metodo_pago'], $row['total_ventas'], $row['total_ingreso'], $row['promedio_venta']],
                    'tendencias' => [$row['fecha'], $row['total_ventas'], $row['total_ingreso']],
                    'sucursal' => [$row['sucursal_nombre'], $row['total_ventas'], $row['total_ingreso'], $row['promedio_venta']],
                    'impuestos' => [$row['metodo'], $row['ventas'], $row['subtotal'], $row['descuento'], $row['base_imponible'], $row['impuesto'], $row['impuesto_calculado'], $row['total']],
                    default => [],
                };
            }

            private function getVentas()
            {
                return Venta::whereBetween('created_at', [$this->desde, $this->hasta])
                    ->with(['detalles', 'usuario', 'sucursal'])
                    ->get()
                    ->map(fn ($v) => [
                        'fecha' => $v->created_at->format('Y-m-d H:i'),
                        'comprobante' => $v->numero_comprobante,
                        'metodo_pago' => $v->metodo_pago,
                        'subtotal' => $v->subtotal,
                        'descuento' => $v->descuento,
                        'impuesto' => $v->impuesto,
                        'total' => $v->total,
                        'pagado' => $v->pagado,
                        'cambio' => $v->cambio,
                        'cajero' => $v->usuario->nombre ?? 'N/A',
                        'sucursal' => $v->sucursal->nombre ?? 'N/A',
                    ]);
            }

            private function getProductos()
            {
                return DetalleVenta::whereHas('venta', function ($q) {
                        $q->whereBetween('created_at', [$this->desde, $this->hasta]);
                    })
                    ->select('producto_id', 'nombre_producto')
                    ->selectRaw('SUM(cantidad) as total_vendido')
                    ->selectRaw('SUM(subtotal) as total_ingreso')
                    ->groupBy('producto_id', 'nombre_producto')
                    ->orderByDesc('total_vendido')
                    ->limit(20)
                    ->get()
                    ->map(fn ($r) => ['nombre' => $r->nombre_producto, 'categoria' => '', 'total_vendido' => $r->total_vendido, 'total_ingreso' => $r->total_ingreso]);
            }

            private function getCategorias()
            {
                return DetalleVenta::whereHas('venta', function ($q) {
                        $q->whereBetween('created_at', [$this->desde, $this->hasta]);
                    })
                    ->join('productos', 'detalles_venta.producto_id', '=', 'productos.id')
                    ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
                    ->select('categorias.nombre as categoria_nombre')
                    ->selectRaw('SUM(detalles_venta.cantidad) as total_vendido')
                    ->selectRaw('SUM(detalles_venta.subtotal) as total_ingreso')
                    ->groupBy('categorias.nombre')
                    ->orderByDesc('total_ingreso')
                    ->get()
                    ->map(fn ($r) => ['categoria_nombre' => $r->categoria_nombre, 'total_vendido' => $r->total_vendido, 'total_ingreso' => $r->total_ingreso]);
            }

            private function getMetodosPago()
            {
                return Venta::whereBetween('created_at', [$this->desde, $this->hasta])
                    ->select('metodo_pago')
                    ->selectRaw('COUNT(*) as total_ventas')
                    ->selectRaw('SUM(total) as total_ingreso')
                    ->selectRaw('AVG(total) as promedio_venta')
                    ->groupBy('metodo_pago')
                    ->orderByDesc('total_ingreso')
                    ->get()
                    ->map(fn ($r) => ['metodo_pago' => $r->metodo_pago, 'total_ventas' => $r->total_ventas, 'total_ingreso' => $r->total_ingreso, 'promedio_venta' => $r->promedio_venta]);
            }

            private function getTendencias()
            {
                return Venta::whereBetween('created_at', [$this->desde, $this->hasta])
                    ->selectRaw('DATE(created_at) as fecha')
                    ->selectRaw('COUNT(*) as total_ventas')
                    ->selectRaw('SUM(total) as total_ingreso')
                    ->groupByRaw('DATE(created_at)')
                    ->orderBy('fecha')
                    ->get()
                    ->map(fn ($r) => ['fecha' => $r->fecha, 'total_ventas' => $r->total_ventas, 'total_ingreso' => $r->total_ingreso]);
            }

            private function getSucursal()
            {
                return Venta::whereBetween('ventas.created_at', [$this->desde, $this->hasta])
                    ->leftJoin('sucursales', 'ventas.sucursal_id', '=', 'sucursales.id')
                    ->selectRaw('COALESCE(sucursales.nombre, "Sin sucursal") as sucursal_nombre')
                    ->selectRaw('COUNT(*) as total_ventas')
                    ->selectRaw('SUM(total) as total_ingreso')
                    ->selectRaw('AVG(total) as promedio_venta')
                    ->groupByRaw('COALESCE(sucursales.nombre, "Sin sucursal")')
                    ->orderByDesc('total_ingreso')
                    ->get()
                    ->map(fn ($r) => ['sucursal_nombre' => $r->sucursal_nombre, 'total_ventas' => $r->total_ventas, 'total_ingreso' => $r->total_ingreso, 'promedio_venta' => $r->promedio_venta]);
            }

            private function getImpuestos()
            {
                $config = ConfiguracionNegocio::obtenerConfiguracion();
                $porcentajeImpuesto = $config?->porcentaje_impuesto ?? 0;

                return Venta::whereBetween('created_at', [$this->desde, $this->hasta])
                    ->select('metodo_pago')
                    ->selectRaw('COUNT(*) as ventas')
                    ->selectRaw('SUM(subtotal) as subtotal')
                    ->selectRaw('SUM(descuento) as descuento')
                    ->selectRaw('SUM(impuesto) as impuesto')
                    ->selectRaw('SUM(total) as total')
                    ->groupBy('metodo_pago')
                    ->orderByDesc('total')
                    ->get()
                    ->map(fn ($r) => [
                        'metodo' => $r->metodo_pago,
                        'ventas' => $r->ventas,
                        'subtotal' => $r->subtotal,
                        'descuento' => $r->descuento,
                        'base_imponible' => $r->subtotal - $r->descuento,
                        'impuesto' => $r->impuesto,
                        'impuesto_calculado' => round(($r->subtotal - $r->descuento) * ($porcentajeImpuesto / 100), 2),
                        'total' => $r->total,
                    ]);
            }
        }, $filename);
    }

    public function exportarPdf(Request $request, string $tipo)
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

        $config = ConfiguracionNegocio::obtenerConfiguracion();
        $porcentajeImpuesto = $config?->porcentaje_impuesto ?? 0;

        $data = match ($tipo) {
            'ventas' => [
                'title' => 'Reporte de Ventas',
                'rows' => Venta::whereBetween('created_at', [$desde, $hasta])
                    ->with(['usuario', 'sucursal'])
                    ->get()
                    ->map(fn ($v) => [
                        'fecha' => $v->created_at->format('d/m/Y H:i'),
                        'comprobante' => $v->numero_comprobante,
                        'metodo' => $v->metodo_pago,
                        'subtotal' => number_format($v->subtotal, 2),
                        'descuento' => number_format($v->descuento, 2),
                        'impuesto' => number_format($v->impuesto, 2),
                        'total' => number_format($v->total, 2),
                        'cajero' => $v->usuario->nombre ?? 'N/A',
                        'sucursal' => $v->sucursal->nombre ?? 'N/A',
                    ]),
            ],
            'productos' => [
                'title' => 'Ranking de Productos',
                'rows' => DetalleVenta::whereHas('venta', function ($q) use ($desde, $hasta) {
                        $q->whereBetween('created_at', [$desde, $hasta]);
                    })
                    ->select('producto_id', 'nombre_producto')
                    ->selectRaw('SUM(cantidad) as total_vendido')
                    ->selectRaw('SUM(subtotal) as total_ingreso')
                    ->groupBy('producto_id', 'nombre_producto')
                    ->orderByDesc('total_vendido')
                    ->limit(20)
                    ->get()
                    ->map(fn ($r) => [
                        'nombre' => $r->nombre_producto,
                        'total_vendido' => $r->total_vendido,
                        'total_ingreso' => number_format($r->total_ingreso, 2),
                    ]),
            ],
            'categorias' => [
                'title' => 'Ventas por Categoria',
                'rows' => DetalleVenta::whereHas('venta', function ($q) use ($desde, $hasta) {
                        $q->whereBetween('created_at', [$desde, $hasta]);
                    })
                    ->join('productos', 'detalles_venta.producto_id', '=', 'productos.id')
                    ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
                    ->select('categorias.nombre as categoria_nombre')
                    ->selectRaw('SUM(detalles_venta.cantidad) as total_vendido')
                    ->selectRaw('SUM(detalles_venta.subtotal) as total_ingreso')
                    ->groupBy('categorias.nombre')
                    ->orderByDesc('total_ingreso')
                    ->get()
                    ->map(fn ($r) => [
                        'categoria' => $r->categoria_nombre,
                        'total_vendido' => $r->total_vendido,
                        'total_ingreso' => number_format($r->total_ingreso, 2),
                    ]),
            ],
            'metodos_pago' => [
                'title' => 'Reporte por Metodo de Pago',
                'rows' => Venta::whereBetween('created_at', [$desde, $hasta])
                    ->select('metodo_pago')
                    ->selectRaw('COUNT(*) as total_ventas')
                    ->selectRaw('SUM(total) as total_ingreso')
                    ->selectRaw('AVG(total) as promedio_venta')
                    ->groupBy('metodo_pago')
                    ->orderByDesc('total_ingreso')
                    ->get()
                    ->map(fn ($r) => [
                        'metodo' => $r->metodo_pago,
                        'ventas' => $r->total_ventas,
                        'ingreso' => number_format($r->total_ingreso, 2),
                        'promedio' => number_format($r->promedio_venta, 2),
                    ]),
            ],
            'tendencias' => [
                'title' => 'Tendencias Comparativas',
                'rows' => Venta::whereBetween('created_at', [$desde, $hasta])
                    ->selectRaw('DATE(created_at) as fecha')
                    ->selectRaw('COUNT(*) as total_ventas')
                    ->selectRaw('SUM(total) as total_ingreso')
                    ->groupByRaw('DATE(created_at)')
                    ->orderBy('fecha')
                    ->get()
                    ->map(fn ($r) => [
                        'fecha' => $r->fecha,
                        'ventas' => $r->total_ventas,
                        'ingreso' => number_format($r->total_ingreso, 2),
                    ]),
            ],
            'sucursal' => [
                'title' => 'Reporte por Sucursal',
                'rows' => Venta::whereBetween('ventas.created_at', [$desde, $hasta])
                    ->leftJoin('sucursales', 'ventas.sucursal_id', '=', 'sucursales.id')
                    ->selectRaw('COALESCE(sucursales.nombre, "Sin sucursal") as sucursal_nombre')
                    ->selectRaw('COUNT(*) as total_ventas')
                    ->selectRaw('SUM(total) as total_ingreso')
                    ->selectRaw('AVG(total) as promedio_venta')
                    ->groupByRaw('COALESCE(sucursales.nombre, "Sin sucursal")')
                    ->orderByDesc('total_ingreso')
                    ->get()
                    ->map(fn ($r) => [
                        'sucursal' => $r->sucursal_nombre,
                        'ventas' => $r->total_ventas,
                        'ingreso' => number_format($r->total_ingreso, 2),
                        'promedio' => number_format($r->promedio_venta, 2),
                    ]),
            ],
            'impuestos' => [
                'title' => 'Reporte de Impuestos (IVA)',
                'porcentaje' => $porcentajeImpuesto,
                'rows' => Venta::whereBetween('created_at', [$desde, $hasta])
                    ->select('metodo_pago')
                    ->selectRaw('COUNT(*) as ventas')
                    ->selectRaw('SUM(subtotal) as subtotal')
                    ->selectRaw('SUM(descuento) as descuento')
                    ->selectRaw('SUM(impuesto) as impuesto')
                    ->selectRaw('SUM(total) as total')
                    ->groupBy('metodo_pago')
                    ->orderByDesc('total')
                    ->get()
                    ->map(fn ($r) => [
                        'metodo' => $r->metodo_pago,
                        'ventas' => $r->ventas,
                        'subtotal' => number_format($r->subtotal, 2),
                        'descuento' => number_format($r->descuento, 2),
                        'base' => number_format($r->subtotal - $r->descuento, 2),
                        'impuesto' => number_format($r->impuesto, 2),
                        'calculado' => number_format(round(($r->subtotal - $r->descuento) * ($porcentajeImpuesto / 100), 2), 2),
                        'total' => number_format($r->total, 2),
                    ]),
            ],
            default => ['title' => '', 'rows' => collect()],
        };

        $pdf = Pdf::loadView('dashboard.pdf-reporte', [
            'title' => $data['title'] ?? 'Reporte',
            'rows' => $data['rows'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'porcentajeImpuesto' => $data['porcentaje'] ?? null,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$tipo}_{$startDate}_{$endDate}.pdf");
    }
}
