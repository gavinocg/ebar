<?php

namespace App\Policies;

use App\Models\Producto;

class ProductoPolicy
{
    public function gestionar(\App\Models\User $user, ?Producto $producto = null): bool
    {
        return $user->esAdminDelNegocioActual();
    }
}