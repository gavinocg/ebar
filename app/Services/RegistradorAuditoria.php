<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

class RegistradorAuditoria
{
    public function registrar(
        string $modulo,
        string $accion,
        ?string $descripcion = null,
        array $detalles = [],
        ?string $tipoReferencia = null,
        ?int $idReferencia = null
    ): Auditoria {
        return Auditoria::create([
            'usuario_id' => Auth::id(),
            'modulo' => $modulo,
            'accion' => $accion,
            'tipo_referencia' => $tipoReferencia,
            'id_referencia' => $idReferencia,
            'descripcion' => $descripcion,
            'detalles' => $detalles,
            'direccion_ip' => request()->ip(),
        ]);
    }
}