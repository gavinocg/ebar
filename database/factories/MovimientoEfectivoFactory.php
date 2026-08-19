<?php

namespace Database\Factories;

use App\Models\MovimientoEfectivo;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoEfectivoFactory extends Factory
{
    protected $model = MovimientoEfectivo::class;

    public function definition(): array
    {
        return [
            'tipo' => 'ingreso',
            'monto' => fake()->randomFloat(2, 1, 500),
            'motivo' => fake()->sentence(),
        ];
    }
}
