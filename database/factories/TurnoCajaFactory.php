<?php

namespace Database\Factories;

use App\Models\TurnoCaja;
use Illuminate\Database\Eloquent\Factories\Factory;

class TurnoCajaFactory extends Factory
{
    protected $model = TurnoCaja::class;

    public function definition(): array
    {
        return [
            'fondo_inicial' => fake()->randomFloat(2, 50, 500),
            'abierto_en' => now(),
            'estado' => 'abierta',
        ];
    }
}
