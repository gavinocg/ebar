<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

class CajaPolicy
{
    public function verArqueos(User $user, ?Caja $caja = null): bool
    {
        return $user->tienePermiso('caja.reporte');
    }

    public function aprobarCuadres(User $user, ?Caja $caja = null): bool
    {
        return $user->tienePermiso('cuadre.aprobar');
    }

    public function reabrir(User $user, ?Caja $caja = null): bool
    {
        return $user->tienePermiso('caja.reabrir');
    }
}
