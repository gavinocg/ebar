<?php

namespace Database\Factories;

use App\Models\Reembolso;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReembolsoFactory extends Factory
{
    protected $model = Reembolso::class;

    public function definition(): array
    {
        return [
            'tipo' => 'parcial',
            'monto' => fake()->randomFloat(2, 1, 100),
            'motivo' => fake()->sentence(),
            'metodo' => 'efectivo',
        ];
    }
}
