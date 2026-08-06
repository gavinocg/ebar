<?php

namespace App\Http\Middleware;

use App\Services\ContextoNegocio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutorizarRolNegocio
{
    public function handle(Request $request, Closure $next, string $rol): Response
    {
        $usuario = $request->user();
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($usuario && $usuario->rolEnNegocio($negocioId) === $rol, 403, 'No tienes permisos para esta acción en el bar.');

        return $next($request);
    }
}