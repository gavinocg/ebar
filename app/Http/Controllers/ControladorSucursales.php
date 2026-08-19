<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Negocio;
use App\Services\ContextoNegocio;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ControladorSucursales extends Controller
{
    public function index(): View
    {
        return view('sucursales.index', [
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'xns' => $this->sucursalesContratadas(),
        ]);
    }

    public function store(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);
        $limite = $this->sucursalesContratadas();

        $activas = Sucursal::where('negocio_id', $negocioId)->where('esta_activa', true)->count();

        if ($limite > 0 && $activas >= $limite) {
            return back()->withErrors(['nombre' => "Límite de sucursales contratadas alcanzado ({$limite})."])->withInput();
        }

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'provincia' => 'nullable|string|max:100',
            'canton' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100',
            'n_cajeros_contratados' => 'nullable|integer|min:0|max:50',
        ]);

        $sucursal = Sucursal::create([
            'negocio_id' => $negocioId,
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'provincia' => $datos['provincia'] ?? null,
            'canton' => $datos['canton'] ?? null,
            'ciudad' => $datos['ciudad'] ?? null,
            'n_cajeros_contratados' => $datos['n_cajeros_contratados'] ?? 1,
            'esta_activa' => true,
        ]);

        $auditoria->registrar('sucursales', 'crear', 'Sucursal creada', [
            'id' => $sucursal->id,
        ], Sucursal::class, $sucursal->id);

        return redirect()->route('sucursales.index')->with('success', 'Sucursal creada.');
    }

    public function update(Request $request, Sucursal $sucursal, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        abort_unless($sucursal->negocio_id === $negocioId, 404);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'provincia' => 'nullable|string|max:100',
            'canton' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100',
            'n_cajeros_contratados' => 'nullable|integer|min:0|max:50',
            'esta_activa' => 'nullable|boolean',
        ]);

        $sucursal->update([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'provincia' => $datos['provincia'] ?? null,
            'canton' => $datos['canton'] ?? null,
            'ciudad' => $datos['ciudad'] ?? null,
            'n_cajeros_contratados' => $datos['n_cajeros_contratados'] ?? $sucursal->n_cajeros_contratados,
            'esta_activa' => $request->boolean('esta_activa'),
        ]);

        $auditoria->registrar('sucursales', 'actualizar', 'Sucursal actualizada', [
            'id' => $sucursal->id,
        ], Sucursal::class, $sucursal->id);

        return redirect()->route('sucursales.index')->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Sucursal $sucursal, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        abort_unless($sucursal->negocio_id === $negocioId, 404);

        if ($sucursal->turnosCaja()->exists()) {
            return back()->withErrors(['nombre' => 'No se puede eliminar una sucursal con historial de turnos.']);
        }

        if ($sucursal->cajas()->exists()) {
            return back()->withErrors(['nombre' => 'No se puede eliminar una sucursal con cajas asociadas.']);
        }

        $auditoria->registrar('sucursales', 'eliminar', 'Sucursal eliminada', [
            'id' => $sucursal->id,
        ], Sucursal::class, $sucursal->id);

        $sucursal->delete();

        return redirect()->route('sucursales.index')->with('success', 'Sucursal eliminada.');
    }

    private function sucursalesContratadas(): int
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::find($negocioId);

        return $negocio ? (int) $negocio->numero_sucursales_contratadas : 0;
    }
}