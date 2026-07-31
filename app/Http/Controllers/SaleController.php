<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load('items.product');
        return view('sales.show', compact('sale'));
    }
}
