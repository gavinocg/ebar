<?php

namespace App\Http\Controllers;

use App\Models\Categoria as Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorCategorias extends Controller
{
    public function index()
    {
        $categories = Category::withCount('productos')->orderBy('nombre')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'icono' => 'nullable|string|max:60|regex:/^bi bi-[a-z0-9-]+$/',
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'orden' => 'required|integer|min:0|max:9999',
            'esta_activa' => 'nullable|boolean',
        ]);

        $datos = $request->only(['nombre', 'descripcion', 'icono', 'color', 'orden']);
        $datos['esta_activa'] = $request->boolean('esta_activa');
        if ($request->hasFile('imagen')) {
            $datos['imagen_path'] = $request->file('imagen')->store('categorias', 'public');
        }
        Category::create($datos);

        return redirect()->route('categorias.index')->with('success', 'Categoría creada');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'icono' => 'nullable|string|max:60|regex:/^bi bi-[a-z0-9-]+$/',
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'orden' => 'required|integer|min:0|max:9999',
            'esta_activa' => 'nullable|boolean',
        ]);

        $datos = $request->only(['nombre', 'descripcion', 'icono', 'color', 'orden']);
        $datos['esta_activa'] = $request->boolean('esta_activa');
        if ($request->hasFile('imagen')) {
            if ($category->imagen_path) {
                Storage::disk('public')->delete($category->imagen_path);
            }
            $datos['imagen_path'] = $request->file('imagen')->store('categorias', 'public');
        }
        $category->update($datos);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada');
    }

    public function destroy(Category $category)
    {
        if ($category->imagen_path) {
            Storage::disk('public')->delete($category->imagen_path);
        }
        $category->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada');
    }
}
