<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Pago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControladorPagos extends Controller
{
    public function store(Request $request, Contrato $contrato): RedirectResponse
    {
        $datos = $request->validate([
            'fecha_pago' => 'required|date',
            'concepto' => 'nullable|string|max:255',
            'forma_pago' => ['required', Rule::in(Pago::FORMAS_PAGO)],
            'valor' => 'required|numeric|min:0.01|max:9999999.99',
            'referencia' => 'nullable|string|max:100',
        ]);

        Pago::create([
            'contrato_id' => $contrato->id,
            'fecha_pago' => $datos['fecha_pago'],
            'concepto' => $datos['concepto'] ?? null,
            'forma_pago' => $datos['forma_pago'],
            'valor' => $datos['valor'],
            'estado' => 'registrado',
            'referencia' => $datos['referencia'] ?? null,
        ]);

        return back()->with('success', 'Pago registrado.');
    }

    public function anular(Pago $pago): RedirectResponse
    {
        if ($pago->estado === 'anulado') {
            return back()->with('error', 'El pago ya estaba anulado.');
        }

        $pago->update(['estado' => 'anulado']);

        return back()->with('success', 'Pago anulado.');
    }
}