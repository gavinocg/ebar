<?php

namespace Database\Factories;

use App\Models\ConteoInventario;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConteoInventarioFactory extends Factory
{
    protected $model = ConteoInventario::class;

    public function definition(): array
    {
        return [
            'numero' => 'CNT-' . str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'fecha' => fake()->date(),
            'estado' => 'borrador',
            'notas' => fake()->sentence(),
        ];
    }
}
