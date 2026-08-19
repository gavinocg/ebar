<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    public function gestionar(User $user, ?Producto $producto = null): bool
    {
        return $user->tienePermiso('producto.crear');
    }
}
