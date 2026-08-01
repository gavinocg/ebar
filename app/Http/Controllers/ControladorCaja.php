<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\TurnoCaja;
use App\Models\MovimientoEfectivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ControladorCaja extends Controller
{
    public function abrir(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fondo_inicial' => 'required|numeric|min:0|max:99999999.99',
        ]);

        if ($this->turnoAbierto()) {
            return back()->withErrors(['caja' => 'Ya tienes un turno de caja abierto.']);
        }

        $caja = Caja::where('esta_activa', true)->orderBy('id')->firstOrFail();

        TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => Auth::id(),
            'fondo_inicial' => $datos['fondo_inicial'],
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        $turno = $this->turnoAbierto();
        MovimientoEfectivo::create([
            'caja_id' => $caja->id,
            'turno_caja_id' => $turno->id,
            'usuario_id' => Auth::id(),
            'tipo' => 'fondo_inicial',
            'monto' => $datos['fondo_inicial'],
            'motivo' => 'Apertura de caja',
        ]);

        return back()->with('success', 'Turno de caja abierto correctamente.');
    }

    public function cerrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'efectivo_contado' => 'required|numeric|min:0|max:99999999.99',
            'notas' => 'nullable|string|max:1000',
        ]);

        $turno = $this->turnoAbierto();

        if (!$turno) {
            return back()->withErrors(['caja' => 'No tienes un turno de caja abierto.']);
        }

        $esperado = round((float) $turno->movimientosEfectivo()->sum('monto'), 2);
        $contado = round((float) $datos['efectivo_contado'], 2);

        $turno->update([
            'cerrado_en' => now(),
            'efectivo_esperado' => $esperado,
            'efectivo_contado' => $contado,
            'diferencia' => round($contado - $esperado, 2),
            'estado' => 'cerrada',
            'notas' => $datos['notas'] ?? null,
        ]);

        return back()->with('success', 'Turno de caja cerrado correctamente.');
    }

    public function movimiento(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => 'required|in:entrada,retiro,gasto',
            'monto' => 'required|numeric|min:0.01|max:99999999.99',
            'motivo' => 'required|string|max:255',
        ]);
        $turno = $this->turnoAbierto();

        if (!$turno) {
            return back()->withErrors(['caja' => 'Debes abrir un turno antes de registrar efectivo.']);
        }

        $monto = in_array($datos['tipo'], ['retiro', 'gasto'], true)
            ? -abs((float) $datos['monto'])
            : abs((float) $datos['monto']);

        MovimientoEfectivo::create([
            'caja_id' => $turno->caja_id,
            'turno_caja_id' => $turno->id,
            'usuario_id' => Auth::id(),
            'tipo' => $datos['tipo'],
            'monto' => $monto,
            'motivo' => $datos['motivo'],
        ]);

        return back()->with('success', 'Movimiento de efectivo registrado.');
    }

    private function turnoAbierto(): ?TurnoCaja
    {
        return TurnoCaja::where('usuario_id', Auth::id())
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }
}
