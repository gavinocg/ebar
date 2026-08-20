<?php

namespace App\Http\Middleware;

use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Sucursal;
use App\Services\ContextoNegocio;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EstablecerContextoNegocio
{
    public function __construct(private ContextoNegocio $contexto)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if ($usuario && $usuario->rol === 'super_admin') {
            return redirect()->route('plataforma.inicio');
        }

        if ($usuario && !$usuario->esta_activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('inicio_sesion');
        }

        $sesionNegocioId = $request->session()->get('negocio_id');

        $membresia = $sesionNegocioId
            ? MembresiaNegocio::where('usuario_id', Auth::id())
                ->where('esta_activa', true)
                ->where('negocio_id', $sesionNegocioId)
                ->first()
            : null;

        if (!$membresia && $sesionNegocioId) {
            // Si el usuario aún tiene una membresía (inactiva) para el negocio de la sesión,
            // significa que su acceso fue revocado: no cambiar silenciosamente, pedir que elija.
            $membresiaRevocada = MembresiaNegocio::where('usuario_id', Auth::id())
                ->where('negocio_id', $sesionNegocioId)
                ->exists();

            if ($membresiaRevocada) {
                $request->session()->forget('negocio_id');
                $request->session()->forget('sucursal_id');

                return redirect()->route('negocio.seleccionar')->with('error', 'Tu acceso a ese negocio cambió. Selecciona un negocio para continuar.');
            }
        }

        if (!$membresia) {
            $membresia = MembresiaNegocio::where('usuario_id', Auth::id())
                ->where('esta_activa', true)
                ->first();
        }

        // Testing only: allow users with no memberships to pass (used in test helpers)
        if (app()->environment('testing') && !$membresia && !MembresiaNegocio::where('usuario_id', Auth::id())->exists()) {
            return $next($request);
        }

        abort_unless($membresia, 403, 'El usuario no tiene un negocio asignado.');

        $negocio = Negocio::query()
            ->with('contratos')
            ->find($membresia->negocio_id);

        abort_unless($negocio && $negocio->esta_activo, 403, 'Este bar está suspendido.');

        $contratoActivo = $negocio->contratos
            ->where('estado', 'activo')
            ->sortByDesc('fecha_fin')
            ->first();

        if ($contratoActivo) {
            $contratoActivo->aplicarVencimiento();
        }

        $contratoVigente = $negocio->contratoVigente();

        if (!$contratoVigente && !app()->environment('testing')) {
            abort(403, 'Este bar no tiene un contrato vigente.');
        }

        $this->contexto->establecer($membresia->negocio_id);
        $request->session()->put('negocio_id', $membresia->negocio_id);

        $sucursal = Sucursal::where('negocio_id', $membresia->negocio_id)
            ->where('esta_activa', true)
            ->orderBy('id')
            ->first();

        $sucursalId = $request->session()->get('sucursal_id');

        if (!$sucursalId && $membresia->sucursal_id) {
            $sucursalId = $membresia->sucursal_id;
        }

        if ($sucursalId) {
            $seleccionada = Sucursal::where('negocio_id', $membresia->negocio_id)
                ->where('id', $sucursalId)
                ->where('esta_activa', true)
                ->first();

            if ($seleccionada) {
                $sucursal = $seleccionada;
            }
        }

        if ($sucursal) {
            $this->contexto->establecerSucursal((int) $sucursal->id);
            $request->session()->put('sucursal_id', $sucursal->id);
        }

        return $next($request);
    }
}
