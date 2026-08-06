<?php

namespace App\Policies;

class ReportePolicy
{
    public function ver(\App\Models\User $user): bool
    {
        return $user->esAdminDelNegocioActual();
    }
}