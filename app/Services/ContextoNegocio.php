<?php

namespace App\Services;

class ContextoNegocio
{
    private ?int $negocioId = null;

    private ?int $sucursalId = null;

    public function establecer(int $negocioId): void
    {
        $this->negocioId = $negocioId;
    }

    public function establecerSucursal(int $sucursalId): void
    {
        $this->sucursalId = $sucursalId;
    }

    public function id(): ?int
    {
        return $this->negocioId;
    }

    public function sucursalId(): ?int
    {
        return $this->sucursalId;
    }
}
