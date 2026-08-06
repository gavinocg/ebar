<?php

namespace App\Policies;

use App\Models\Venta;

class VentaPolicy
{
    public function administrar(\App\Models\User $user, ?Venta $venta = null): bool
    {
        return $user->esAdminDelNegocioActual();
    }
}