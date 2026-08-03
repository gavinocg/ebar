<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutorizarSuperAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->rol === 'super_admin', 403, 'Se requiere acceso de super administrador.');

        return $next($request);
    }
}
