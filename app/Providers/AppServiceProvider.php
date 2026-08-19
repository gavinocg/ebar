<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\ConfiguracionNegocio;
use App\Policies\ConfiguracionPolicy;
use App\Services\ContextoNegocio;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ContextoNegocio::class, fn () => new ContextoNegocio());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ConfiguracionNegocio::class, ConfiguracionPolicy::class);

        Gate::define('reportes.ver', function ($usuario) {
            return $usuario->tienePermiso('reporte.ventas');
        });

        Gate::define('reportes.cajeros', function ($usuario) {
            return $usuario->tienePermiso('reporte.cajeros');
        });

        Gate::define('reportes.ventas_o_cajeros', function ($usuario) {
            return $usuario->tienePermiso('reporte.ventas') || $usuario->tienePermiso('reporte.cajeros');
        });

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
