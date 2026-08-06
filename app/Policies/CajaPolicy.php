<?php

namespace App\Policies;

use App\Models\Caja;

class CajaPolicy
{
    public function administrar(\App\Models\User $user, ?Caja $caja = null): bool
    {
        return $user->esAdminDelNegocioActual();
    }
}