<?php

namespace App\Policies;

use App\Models\ConfiguracionNegocio;

class ConfiguracionPolicy
{
    public function administrar(\App\Models\User $user, ?ConfiguracionNegocio $configuracion = null): bool
    {
        return $user->esAdminDelNegocioActual();
    }
}