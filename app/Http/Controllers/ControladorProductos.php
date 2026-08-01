<?php

namespace App\Http\Controllers;

use App\Models\Producto as Product;
use App\Models\Categoria as Category;
use Illuminate\Http\Request;

class ControladorProductos extends Controller
{
    public function index()
    {
        $products = Product::with('categoria')->orderBy('nombre')->paginate(15);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('nombre')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'existencias' => 'required|integer|min:0',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras',
        ]);

        Product::create($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto creado');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('nombre')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'existencias' => 'required|integer|min:0',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras,' . $product->id,
        ]);

        $product->update($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto actualizado');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado');
    }
}
