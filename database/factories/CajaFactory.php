<?php

namespace Database\Factories;

use App\Models\Caja;
use Illuminate\Database\Eloquent\Factories\Factory;

class CajaFactory extends Factory
{
    protected $model = Caja::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Caja ' . fake()->numberBetween(1, 10),
            'esta_activa' => true,
        ];
    }
}
