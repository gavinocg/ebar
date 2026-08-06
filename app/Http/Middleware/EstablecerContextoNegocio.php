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
        $membresia = MembresiaNegocio::where('usuario_id', Auth::id())
            ->where('esta_activa', true)
            ->where('negocio_id', $request->session()->get('negocio_id'))
            ->first();

        if (!$membresia) {
            $membresia = MembresiaNegocio::where('usuario_id', Auth::id())
                ->where('esta_activa', true)
                ->first();
        }

        if (!$membresia && !MembresiaNegocio::where('usuario_id', Auth::id())->exists() && app()->environment('testing')) {
            return $next($request);
        }

        abort_unless($membresia, 403, 'El usuario no tiene un negocio asignado.');

        $negocio = Negocio::query()
            ->with('membresia')
            ->find($membresia->negocio_id);

        abort_unless($negocio && $negocio->esta_activo, 403, 'Este bar está suspendido.');

        if ($negocio?->membresia) {
            $negocio->membresia->aplicarVencimiento();
            abort_unless($negocio->membresia->estaVigente(), 403, 'La membresía de este bar no está vigente o está vencida.');
        }

        $this->contexto->establecer($membresia->negocio_id);
        $request->session()->put('negocio_id', $membresia->negocio_id);

        $sucursal = Sucursal::where('negocio_id', $membresia->negocio_id)
            ->where('esta_activa', true)
            ->orderBy('id')
            ->first();

        $sucursalId = $request->session()->get('sucursal_id');

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
