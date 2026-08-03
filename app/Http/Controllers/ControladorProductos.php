<?php

namespace App\Http\Controllers;

use App\Models\Producto as Product;
use App\Models\Categoria as Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'imagen' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'distintivo' => 'nullable|string|max:40',
            'distintivo_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'destacado' => 'nullable|boolean',
            'precio' => 'required|numeric|min:0',
            'existencias' => 'required_if:maneja_existencias,1|nullable|integer|min:0',
            'maneja_existencias' => 'nullable|boolean',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras',
        ]);

        $datos = $request->only([
            'categoria_id', 'nombre', 'descripcion', 'precio', 'existencias', 'codigo_barras', 'maneja_existencias',
            'color', 'distintivo', 'distintivo_color',
        ]);
        $datos['esta_activo'] = $request->boolean('esta_activo');
        $datos['maneja_existencias'] = $request->boolean('maneja_existencias');
        $datos['existencias'] = $datos['maneja_existencias'] ? ($datos['existencias'] ?? 0) : 0;
        $datos['destacado'] = $request->boolean('destacado');
        if ($request->hasFile('imagen')) {
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }
        Product::create($datos);

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
            'imagen' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'distintivo' => 'nullable|string|max:40',
            'distintivo_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'destacado' => 'nullable|boolean',
            'precio' => 'required|numeric|min:0',
            'existencias' => 'required_if:maneja_existencias,1|nullable|integer|min:0',
            'maneja_existencias' => 'nullable|boolean',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras,' . $product->id,
        ]);

        $datos = $request->only([
            'categoria_id', 'nombre', 'descripcion', 'precio', 'existencias', 'codigo_barras', 'maneja_existencias',
            'color', 'distintivo', 'distintivo_color',
        ]);
        $datos['esta_activo'] = $request->boolean('esta_activo');
        $datos['maneja_existencias'] = $request->boolean('maneja_existencias');
        $datos['existencias'] = $datos['maneja_existencias'] ? ($datos['existencias'] ?? 0) : 0;
        $datos['destacado'] = $request->boolean('destacado');
        if ($request->hasFile('imagen')) {
            if ($product->imagen_path) {
                Storage::disk('public')->delete($product->imagen_path);
            }
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }
        $product->update($datos);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado');
    }

    public function destroy(Product $product)
    {
        if ($product->imagen_path) {
            Storage::disk('public')->delete($product->imagen_path);
        }
        $product->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado');
    }
}
