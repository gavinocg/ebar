<?php

namespace App\Policies;

use App\Models\TurnoCajero;
use App\Models\User;

class TurnoCajeroPolicy
{
    public function verArqueos(User $user, ?TurnoCajero $turnoCajero = null): bool
    {
        return $user->tienePermiso('caja.reporte');
    }

    public function aprobarCuadres(User $user, ?TurnoCajero $turnoCajero = null): bool
    {
        return $user->tienePermiso('cuadre.aprobar');
    }

    public function reabrir(User $user, ?TurnoCajero $turnoCajero = null): bool
    {
        return $user->tienePermiso('caja.reabrir');
    }
}