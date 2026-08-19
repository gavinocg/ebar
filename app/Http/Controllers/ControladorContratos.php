<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Negocio;
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
            'referencia' => 'nullable|string|max:100',
        ]);

        abort_if(
            Contrato::where('negocio_id', $negocio->id)->where('estado', 'activo')->exists(),
            422,
            'Este bar ya tiene un contrato activo. Cámbiale el estado antes de registrar otro.'
        );

        Contrato::create([
            'negocio_id' => $negocio->id,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'forma_contratacion' => $datos['forma_contratacion'],
            'estado' => 'activo',
            'referencia' => $datos['referencia'] ?? null,
        ]);

        return back()->with('success', 'Contrato registrado.');
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
        if ($contrato->pagos()->where('estado', 'registrado')->exists()) {
            return back()->with('error', 'No se puede eliminar un contrato con pagos registrados.');
        }

        $contrato->delete();

        return back()->with('success', 'Contrato eliminado.');
    }
}