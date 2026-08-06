<?php

namespace App\Policies;

use App\Models\Venta;
use App\Models\User;

class VentaPolicy
{
    public function administrar(User $user, ?Venta $venta = null): bool
    {
        return $user->esAdminDelNegocioActual();
    }

    public function reembolsar(User $user, ?Venta $venta = null): bool
    {
        return $user->esAdminDelNegocioActual();
    }
}