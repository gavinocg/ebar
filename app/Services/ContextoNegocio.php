<?php

namespace App\Services;

class ContextoNegocio
{
    private ?int $negocioId = null;

    public function establecer(int $negocioId): void
    {
        $this->negocioId = $negocioId;
    }

    public function id(): ?int
    {
        return $this->negocioId;
    }
}
