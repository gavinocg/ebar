<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('inicio_sesion'));
        $middleware->alias([
            'negocio' => \App\Http\Middleware\EstablecerContextoNegocio::class,
            'super_admin' => \App\Http\Middleware\AutorizarSuperAdministrador::class,
            'rol_negocio' => \App\Http\Middleware\AutorizarRolNegocio::class,
            'forzar_cambio_password' => \App\Http\Middleware\ForzarCambioPassword::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
