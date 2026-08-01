<?php

namespace App\Http\Controllers;

use App\Models\Categoria as Category;
use Illuminate\Http\Request;

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
        ]);

        Category::create($request->only(['nombre', 'descripcion']));

        return redirect()->route('categorias.index')->with('success', 'Categoría creada');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $category->update($request->only(['nombre', 'descripcion']));

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada');
    }
}
