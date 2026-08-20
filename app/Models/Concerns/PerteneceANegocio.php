<?php

namespace App\Models\Concerns;

use App\Services\ContextoNegocio;
use Illuminate\Database\Eloquent\Builder;

trait PerteneceANegocio
{
    protected static function bootPerteneceANegocio(): void
    {
        static::addGlobalScope('negocio', function (Builder $builder): void {
            $negocioId = app(ContextoNegocio::class)->id();

            if ($negocioId !== null) {
                $builder->where($builder->getModel()->getTable() . '.negocio_id', $negocioId);
            } elseif (!app()->environment('testing')) {
                throw new \RuntimeException('ContextoNegocio no establecido. No se pueden consultar registros sin un negocio activo.');
            }
        });

        static::creating(function ($modelo): void {
            $negocioId = app(ContextoNegocio::class)->id();

            if ($negocioId !== null && empty($modelo->negocio_id)) {
                $modelo->negocio_id = $negocioId;
            }
        });
    }
}
