<?php

namespace App\Http\Controllers;

use App\Models\Categoria as Category;
use App\Services\GuardiaEliminacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
            'icono' => ['nullable', Rule::in($this->iconosDeComida())],
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
            'icono' => ['nullable', Rule::in($this->iconosDeComida())],
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
        $dependencias = GuardiaEliminacion::categoriaConDependencias($category->id);

        if ($dependencias) {
            return back()->with('no_eliminable', [
                'entidad' => 'categoría',
                'dependencias' => array_values(array_unique($dependencias)),
                'url' => route('categorias.desactivar', $category),
            ]);
        }

        if ($category->imagen_path) {
            Storage::disk('public')->delete($category->imagen_path);
        }
        $category->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada');
    }

    public function desactivar(Category $category): RedirectResponse
    {
        $category->esta_activa = false;
        $category->save();

        return redirect()->route('categorias.index')->with('success', 'Categoría desactivada por tener productos asociados.');
    }

    private function iconosDeComida(): array
    {
        return [
            'bi bi-cup-straw',
            'bi bi-cup-hot',
            'bi bi-egg-fried',
            'bi bi-cake2',
            'bi bi-ice-cream',
            'bi bi-apple',
            'bi bi-basket2',
            'bi bi-people',
        ];
    }
}
