<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForzarCambioPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->debe_cambiar_password) {
            if ($request->routeIs('password.cambiar', 'password.cambiar.guardar', 'cerrar_sesion')) {
                return $next($request);
            }

            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}