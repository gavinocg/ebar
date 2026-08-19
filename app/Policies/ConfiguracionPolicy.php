<?php

namespace App\Policies;

use App\Models\ConfiguracionNegocio;
use App\Models\User;

class ConfiguracionPolicy
{
    public function administrar(User $user, ?ConfiguracionNegocio $configuracion = null): bool
    {
        return $user->tienePermiso('configuracion.negocio');
    }
}
