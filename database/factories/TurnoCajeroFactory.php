<?php

namespace Database\Factories;

use App\Models\TurnoCajero;
use Illuminate\Database\Eloquent\Factories\Factory;

class TurnoCajeroFactory extends Factory
{
    protected $model = TurnoCajero::class;

    public function definition(): array
    {
        return [
            'fondo_inicial' => fake()->randomFloat(2, 50, 500),
            'abierto_en' => now(),
            'estado' => 'abierta',
        ];
    }
}