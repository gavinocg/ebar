<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Negocio;
use App\Services\ContextoNegocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladorSucursales extends Controller
{
    public function index(): View
    {
        return view('sucursales.index', [
            'sucursales' => Sucursal::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);
        $limite = (int) ($negocio->membresia?->plan?->limite_sucursales ?? 0);

        $activas = Sucursal::where('negocio_id', $negocioId)->where('esta_activa', true)->count();

        if ($limite > 0 && $activas >= $limite) {
            return back()->withErrors(['nombre' => "Límite de sucursales alcanzado ({$limite})."])->withInput();
        }

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
        ]);

        Sucursal::create([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'telefono' => $datos['telefono'],
            'esta_activa' => true,
        ]);

        return redirect()->route('sucursales.index')->with('success', 'Sucursal creada.');
    }

    public function update(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'esta_activa' => 'nullable|boolean',
        ]);

        $sucursal->update([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'],
            'telefono' => $datos['telefono'],
            'esta_activa' => $request->boolean('esta_activa'),
        ]);

        return redirect()->route('sucursales.index')->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Sucursal $sucursal): RedirectResponse
    {
        $sucursal->delete();

        return redirect()->route('sucursales.index')->with('success', 'Sucursal eliminada.');
    }
}