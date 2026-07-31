<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $salesToday = Sale::whereDate('created_at', $today)->count();
        $revenueToday = Sale::whereDate('created_at', $today)->sum('total');

        $salesMonth = Sale::where('created_at', '>=', $monthStart)->count();
        $revenueMonth = Sale::where('created_at', '>=', $monthStart)->sum('total');

        $productsCount = Product::where('is_active', true)->count();
        $categoriesCount = Category::count();

        $recentSales = Sale::with('items')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $lowStockProducts = Product::where('stock', '<=', 10)
            ->where('is_active', true)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'salesToday',
            'revenueToday',
            'salesMonth',
            'revenueMonth',
            'productsCount',
            'categoriesCount',
            'recentSales',
            'lowStockProducts'
        ));
    }

    public function salesReport(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $sales = Sale::with('items')
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

    public function inventoryReport()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalProducts = $products->count();
        $totalStock = $products->sum('stock');
        $totalValue = $products->sum(function ($product) {
            return $product->stock * $product->price;
        });

        $lowStock = $products->filter(function ($product) {
            return $product->stock <= 10;
        });

        $outOfStock = $products->filter(function ($product) {
            return $product->stock == 0;
        });

        $byCategory = Product::select('categories.name as category_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(stock) as total_stock'))
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.is_active', true)
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
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
