<?php

namespace Database\Factories;

use App\Models\MembresiaNegocio;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembresiaNegocioFactory extends Factory
{
    protected $model = MembresiaNegocio::class;

    public function definition(): array
    {
        return [
            'rol' => 'cajero',
            'esta_activa' => true,
            'cuadre_activo' => false,
            'aprobacion_activa' => false,
            'limite_cajeros' => 10,
        ];
    }
}
