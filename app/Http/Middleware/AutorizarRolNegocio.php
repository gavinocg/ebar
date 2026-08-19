<?php

namespace App\Http\Middleware;

use App\Services\ContextoNegocio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutorizarRolNegocio
{
    private const JERARQUIA = [
        'cajero' => 1,
        'admin_bar' => 2,
        'administrador' => 3,
        'propietario' => 3,
    ];

    public function handle(Request $request, Closure $next, string $rol): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->rol === 'super_admin') {
            return redirect()->route('plataforma.inicio');
        }

        $negocioId = app(ContextoNegocio::class)->id();
        $rolUsuario = $usuario ? $usuario->rolEnNegocio($negocioId) : null;
        $nivelUsuario = self::JERARQUIA[$rolUsuario] ?? 0;

        if ($rol === 'cajero') {
            if ($rolUsuario === 'cajero') {
                return $next($request);
            }

            return redirect()->route('panel.inicio');
        }

        $nivelRequerido = self::JERARQUIA[$rol] ?? 0;

        abort_unless($nivelUsuario >= $nivelRequerido, 403, 'No tienes permisos para esta acción en el bar.');

        return $next($request);
    }
}