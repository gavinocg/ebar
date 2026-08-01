<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\TurnoCaja;
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

        $efectivoVendido = $turno->ventas()
            ->where('metodo_pago', 'efectivo')
            ->sum('pagado');
        $esperado = round((float) $turno->fondo_inicial + (float) $efectivoVendido, 2);
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

    private function turnoAbierto(): ?TurnoCaja
    {
        return TurnoCaja::where('usuario_id', Auth::id())
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }
}
