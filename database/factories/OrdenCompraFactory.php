<?php

namespace Database\Factories;

use App\Models\OrdenCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    protected $model = OrdenCompra::class;

    public function definition(): array
    {
        return [
            'numero' => 'OC-' . str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'fecha' => now()->toDateString(),
            'estado' => 'borrador',
            'subtotal' => 0,
            'impuesto' => 0,
            'total' => 0,
            'notas' => fake()->sentence(),
        ];
    }
}
