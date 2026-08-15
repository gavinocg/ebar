<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->words(2, true),
            'precio' => fake()->randomFloat(2, 1, 100),
            'existencias' => fake()->numberBetween(0, 100),
            'maneja_existencias' => true,
            'esta_activo' => true,
            'descuento' => 0,
        ];
    }
}
