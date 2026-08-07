<?php

namespace App\Http\Controllers;

use App\Models\Venta as Sale;
use App\Models\Producto as Product;
use App\Models\Categoria as Category;
use App\Models\Caja;
use App\Models\TurnoCaja;
use App\Models\Sucursal;
use App\Services\ContextoNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ControladorPanel extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $salesToday = Sale::whereDate('created_at', $today)->count();
        $revenueToday = Sale::whereDate('created_at', $today)->sum('total');

        $salesMonth = Sale::where('created_at', '>=', $monthStart)->count();
        $revenueMonth = Sale::where('created_at', '>=', $monthStart)->sum('total');

        $productsCount = Product::where('esta_activo', true)->count();
        $categoriesCount = Category::count();

        $recentSales = Sale::with('detalles')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $lowStockProducts = Product::where('maneja_existencias', true)
            ->where('esta_activo', true)
            ->whereNotNull('nivel_minimo')
            ->whereColumn('existencias', '<=', 'nivel_minimo')
            ->orderByRaw('existencias - nivel_minimo asc')
            ->limit(10)
            ->get();

        $cajaActiva = Caja::where('esta_activa', true)->orderBy('id')->first();
        $turnoCaja = TurnoCaja::where('usuario_id', Auth::id())
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();

        return view('dashboard.index', compact(
            'salesToday',
            'revenueToday',
            'salesMonth',
            'revenueMonth',
            'productsCount',
            'categoriesCount',
            'recentSales',
            'lowStockProducts',
            'cajaActiva',
            'turnoCaja'
        ));
    }

    public function reporteVentas(Request $request)
    {
        $this->authorize('reportes.ver');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());
        $sucursalId = $request->get('sucursal_id');

        $negocioId = app(ContextoNegocio::class)->id();
        $sucursales = Sucursal::where('negocio_id', $negocioId)->where('esta_activa', true)->orderBy('nombre')->get();

        $desde = $startDate . ' 00:00:00';
        $hasta = $endDate . ' 23:59:59';

        $query = Sale::query()->whereBetween('created_at', [$desde, $hasta]);
        if ($sucursalId) {
            $query->where('sucursal_id', $sucursalId);
        }

        $sales = $query->with('detalles')->orderBy('created_at', 'desc')->get();

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('total');
        $averageTicket = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        $salesByDayQuery = Sale::whereBetween('created_at', [$desde, $hasta]);
        if ($sucursalId) {
            $salesByDayQuery->where('sucursal_id', $sucursalId);
        }
        $salesByDay = $salesByDayQuery
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return view('dashboard.sales-report', compact(
            'sales',
            'totalSales',
            'totalRevenue',
            'averageTicket',
            'salesByDay',
            'startDate',
            'endDate',
            'sucursales',
            'sucursalId'
        ));
    }

    public function reportePorCajero(Request $request)
    {
        $this->authorize('reportes.ver');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $porCajero = Sale::query()
            ->join('usuarios', 'usuarios.id', '=', 'ventas.usuario_id')
            ->whereBetween('ventas.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select(
                'usuarios.id as usuario_id',
                'usuarios.nombre as nombre',
                DB::raw('COUNT(*) as total_ventas'),
                DB::raw('SUM(ventas.subtotal) as total_subtotal'),
                DB::raw('SUM(ventas.impuesto) as total_impuesto'),
                DB::raw('SUM(ventas.total) as total_ingresos'),
            )
            ->groupBy('usuarios.id', 'usuarios.nombre')
            ->orderByDesc('total_ventas')
            ->get();

        $granTotalVentas = $porCajero->sum('total_ventas');
        $granTotalIngresos = $porCajero->sum('total_ingresos');

        return view('dashboard.cashier-report', compact(
            'porCajero',
            'granTotalVentas',
            'granTotalIngresos',
            'startDate',
            'endDate',
        ));
    }

    public function reporteInventario()
    {
        $this->authorize('reportes.ver');
        $products = Product::with('categoria')
            ->where('esta_activo', true)
            ->orderBy('nombre')
            ->get();

        $totalProducts = $products->count();
        $totalStock = $products->sum('existencias');
        $totalValue = $products->where('maneja_existencias', true)->sum(function ($product) {
            return $product->existencias * $product->precio;
        });

        $lowStock = $products->filter(function ($product) {
            return $product->maneja_existencias && $product->existencias <= 10;
        });

        $outOfStock = $products->filter(function ($product) {
            return $product->maneja_existencias && $product->existencias == 0;
        });

        $byCategory = Product::select('categorias.nombre as nombre_categoria', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(productos.existencias) as existencias_totales'))
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->where('productos.esta_activo', true)
            ->where('productos.maneja_existencias', true)
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderBy('categorias.nombre')
            ->get();

        return view('dashboard.inventory-report', compact(
            'products',
            'totalProducts',
            'totalStock',
            'totalValue',
            'lowStock',
            'outOfStock',
            'byCategory'
        ));
    }
}


