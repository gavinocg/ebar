<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladorEtiquetas extends Controller
{
    public function index(): View
    {
        $productos = Producto::orderBy('nombre')->get();

        return view('products.etiquetas', compact('productos'));
    }

    public function imprimir(Request $request): View
    {
        $datos = $request->validate([
            'productos' => 'required|array|min:1|max:200',
            'productos.*' => 'integer|exists:productos,id',
            'copias' => 'nullable|integer|min:1|max:100',
        ]);

        $productos = Producto::whereIn('id', $datos['productos'])->orderBy('nombre')->get();

        return view('products.etiquetas-imprimir', [
            'productos' => $productos,
            'copias' => (int) ($datos['copias'] ?? 1),
        ]);
    }
}