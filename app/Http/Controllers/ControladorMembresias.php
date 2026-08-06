<?php

namespace App\Http\Controllers;

use App\Models\Membresia;
use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;

class ControladorMembresias extends Controller
{
    public function renovar(Negocio $negocio): RedirectResponse
    {
        $membresia = $negocio->membresia;

        abort_unless($membresia, 404, 'El bar no tiene membresía.');

        $membresia->update([
            'estado' => $membresia->estado === 'cancelada' ? 'cancelada' : 'activa',
            'fecha_vencimiento' => max($membresia->fecha_vencimiento, now())->addDays(max((int) $membresia->plan->duracion_dias, 1)),
            'fecha_renovacion' => now(),
        ]);

        return back()->with('success', 'Membresía renovada hasta ' . $membresia->fecha_vencimiento->format('d/m/Y') . '.');
    }

    public function suspender(Negocio $negocio): RedirectResponse
    {
        abort_unless($negocio->membresia, 404, 'El bar no tiene membresía.');

        $negocio->membresia->update(['estado' => 'suspendida']);

        return back()->with('success', 'Membresía suspendida.');
    }

    public function reactivar(Negocio $negocio): RedirectResponse
    {
        abort_unless($negocio->membresia, 404, 'El bar no tiene membresía.');

        $membresia = $negocio->membresia;
        $membresia->update([
            'estado' => $membresia->estaVencida() ? 'vencida' : 'activa',
        ]);

        return back()->with('success', 'Membresía reactivada.');
    }
}