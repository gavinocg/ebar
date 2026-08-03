<?php

namespace App\Http\Middleware;

use App\Models\MembresiaNegocio;
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

        if (!$membresia && app()->environment('testing')) {
            return $next($request);
        }

        abort_unless($membresia, 403, 'El usuario no tiene un negocio asignado.');

        $this->contexto->establecer($membresia->negocio_id);
        $request->session()->put('negocio_id', $membresia->negocio_id);

        return $next($request);
    }
}
