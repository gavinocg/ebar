<?php

namespace Database\Factories;

use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

class NegocioFactory extends Factory
{
    protected $model = Negocio::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'identificador' => fake()->unique()->slug(),
            'esta_activo' => true,
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
        ];
    }
}
