<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Negocio;
use App\Models\Sucursal;
use App\Services\ContextoNegocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladorCajas extends Controller
{
    public function index(): View
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);

        $cajas = Caja::with('sucursal')->orderByDesc('esta_activa')->orderBy('nombre')->get();

        return view('cajas.index', [
            'cajas' => $cajas,
            'sucursales' => Sucursal::where('esta_activa', true)->orderBy('nombre')->get(),
            'limiteCajas' => $negocio->membresia?->plan?->limite_cajas ?? 0,
            'limiteAlcanzado' => $cajas->where('esta_activa', true)->count() >= ($negocio->membresia?->plan?->limite_cajas ?? 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);
        $limite = (int) ($negocio->membresia?->plan?->limite_cajas ?? 0);

        $activas = Caja::where('negocio_id', $negocioId)->where('esta_activa', true)->count();

        if ($limite > 0 && $activas >= $limite) {
            return back()->withErrors(['nombre' => "Límite de cajas alcanzado ({$limite})."])->withInput();
        }

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'sucursal_id' => 'nullable|integer|exists:sucursales,id',
        ]);

        Caja::create([
            'nombre' => $datos['nombre'],
            'sucursal_id' => $datos['sucursal_id'] ?? null,
            'esta_activa' => true,
        ]);

        return redirect()->route('cajas.index')->with('success', 'Caja creada.');
    }

    public function update(Request $request, Caja $caja): RedirectResponse
    {
        $this->validarCajaDelNegocio($caja);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'sucursal_id' => 'nullable|integer|exists:sucursales,id',
            'esta_activa' => 'nullable|boolean',
        ]);

        $caja->update([
            'nombre' => $datos['nombre'],
            'sucursal_id' => $datos['sucursal_id'] ?? null,
            'esta_activa' => $request->boolean('esta_activa'),
        ]);

        return redirect()->route('cajas.index')->with('success', 'Caja actualizada.');
    }

    public function destroy(Caja $caja): RedirectResponse
    {
        $this->validarCajaDelNegocio($caja);

        if ($caja->turnos()->where('estado', 'abierta')->exists()) {
            return back()->withErrors(['nombre' => 'No se puede eliminar una caja con turnos abiertos.']);
        }

        $caja->delete();

        return redirect()->route('cajas.index')->with('success', 'Caja eliminada.');
    }

    private function validarCajaDelNegocio(Caja $caja): void
    {
        abort_unless(
            Caja::where('id', $caja->id)->where('negocio_id', app(ContextoNegocio::class)->id())->exists(),
            404,
            'La caja no pertenece a este bar.'
        );
    }
}