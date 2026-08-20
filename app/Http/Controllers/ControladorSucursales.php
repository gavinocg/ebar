<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Negocio;
use App\Services\ContextoNegocio;
use App\Services\GuardiaEliminacion;
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
        $negocio = Negocio::findOrFail($negocioId);
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
            'esta_activa' => 'nullable|boolean',
        ]);

        $sucursal = Sucursal::create([
            'negocio_id' => $negocioId,
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'provincia' => $datos['provincia'] ?? null,
            'canton' => $datos['canton'] ?? null,
            'ciudad' => $datos['ciudad'] ?? null,
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
            'esta_activa' => 'nullable|boolean',
        ]);

        $sucursal->update([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'provincia' => $datos['provincia'] ?? null,
            'canton' => $datos['canton'] ?? null,
            'ciudad' => $datos['ciudad'] ?? null,
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

        $dependencias = GuardiaEliminacion::sucursalConDependencias($sucursal->id);

        if ($dependencias) {
            return back()->with('no_eliminable', [
                'entidad' => 'sucursal',
                'dependencias' => array_values(array_unique($dependencias)),
                'url' => route('sucursales.desactivar', $sucursal),
            ]);
        }

        $auditoria->registrar('sucursales', 'eliminar', 'Sucursal eliminada', [
            'id' => $sucursal->id,
        ], Sucursal::class, $sucursal->id);

        $sucursal->delete();

        return redirect()->route('sucursales.index')->with('success', 'Sucursal eliminada.');
    }

    public function desactivar(Sucursal $sucursal, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        abort_unless($sucursal->negocio_id === $negocioId, 404);

        $sucursal->esta_activa = false;
        $sucursal->save();

        $auditoria->registrar('sucursales', 'desactivar', 'Sucursal desactivada', [
            'id' => $sucursal->id,
        ], Sucursal::class, $sucursal->id);

        return redirect()->route('sucursales.index')->with('success', 'Sucursal desactivada.');
    }

    private function sucursalesContratadas(): int
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::find($negocioId);

        if (!$negocio) {
            return 0;
        }

        $contrato = $negocio->contratoVigente();

        if (!$contrato) {
            return 0;
        }

        return $contrato->sucursales_ilimitadas ? 0 : (int) $contrato->numero_sucursales_contratadas;
    }
}