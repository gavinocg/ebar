<?php

namespace App\Http\Controllers;

use App\Models\Venta as Sale;
use App\Models\Producto as Product;
use App\Models\Categoria as Category;
use App\Models\Caja;
use App\Models\TurnoCaja;
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

        $lowStockProducts = Product::where('existencias', '<=', 10)
            ->where('esta_activo', true)
            ->orderBy('existencias', 'asc')
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
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $sales = Sale::with('detalles')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('total');
        $averageTicket = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        $salesByDay = Sale::whereBetween('created_at', [$startDate, $endDate])
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
            'endDate'
        ));
    }

    public function reporteInventario()
    {
        $products = Product::with('categoria')
            ->where('esta_activo', true)
            ->orderBy('nombre')
            ->get();

        $totalProducts = $products->count();
        $totalStock = $products->sum('existencias');
        $totalValue = $products->sum(function ($product) {
            return $product->existencias * $product->precio;
        });

        $lowStock = $products->filter(function ($product) {
            return $product->existencias <= 10;
        });

        $outOfStock = $products->filter(function ($product) {
            return $product->existencias == 0;
        });

        $byCategory = Product::select('categorias.nombre as nombre_categoria', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(productos.existencias) as existencias_totales'))
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->where('productos.esta_activo', true)
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
