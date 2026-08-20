<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Negocio;
use App\Services\GuardiaEliminacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControladorContratos extends Controller
{
    public function store(Request $request, Negocio $negocio): RedirectResponse
    {
        $datos = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'forma_contratacion' => ['required', Rule::in(Contrato::FORMAS)],
            'valor' => 'required|numeric|min:0.01|max:9999999.99',
            'numero_sucursales_contratadas' => 'required|integer|min:1|max:1000',
            'sucursales_ilimitadas' => 'nullable|boolean',
            'numero_cajeros_contratados' => 'required|integer|min:1|max:1000',
            'cajeros_ilimitados' => 'nullable|boolean',
            'referencia' => 'nullable|string|max:100',
        ]);

        abort_if(
            Contrato::where('negocio_id', $negocio->id)->whereIn('estado', ['pendiente', 'activo'])->exists(),
            422,
            'Este bar ya tiene un contrato pendiente o activo. Cámbiale el estado antes de registrar otro.'
        );

        Contrato::create([
            'negocio_id' => $negocio->id,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'forma_contratacion' => $datos['forma_contratacion'],
            'valor' => $datos['valor'],
            'numero_sucursales_contratadas' => $datos['numero_sucursales_contratadas'],
            'sucursales_ilimitadas' => $request->boolean('sucursales_ilimitadas'),
            'numero_cajeros_contratados' => $datos['numero_cajeros_contratados'],
            'cajeros_ilimitados' => $request->boolean('cajeros_ilimitados'),
            'estado' => 'pendiente',
            'referencia' => $datos['referencia'] ?? null,
        ]);

        return back()->with('success', 'Contrato registrado (pendiente de pago).');
    }

    public function estado(Request $request, Contrato $contrato): RedirectResponse
    {
        $contrato->update(['estado' => $request->validate([
            'estado' => ['required', Rule::in(Contrato::ESTADOS)],
        ])['estado']]);

        return back()->with('success', 'Estado del contrato actualizado.');
    }

    public function destroy(Contrato $contrato): RedirectResponse
    {
        $dependencias = GuardiaEliminacion::contratoConDependencias($contrato->id);

        if ($dependencias) {
            return back()->with('no_eliminable', [
                'entidad' => 'contrato',
                'dependencias' => array_values(array_unique($dependencias)),
                'url' => route('plataforma.contratos.desactivar', $contrato),
            ]);
        }

        $contrato->delete();

        return back()->with('success', 'Contrato eliminado.');
    }

    public function desactivar(Contrato $contrato): RedirectResponse
    {
        $contrato->update(['estado' => 'suspendido']);

        return back()->with('success', 'Contrato suspendido por tener pagos registrados.');
    }
}