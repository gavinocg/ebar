<?php

namespace App\Http\Controllers;

use App\Models\Venta as Sale;
use Illuminate\Http\Request;

class ControladorVentas extends Controller
{
    public function index()
    {
        $sales = Sale::withCount('detalles')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load('detalles.producto');
        return view('sales.show', compact('sale'));
    }
}
