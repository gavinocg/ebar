<?php

namespace Database\Factories;

use App\Models\MovimientoInventario;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoInventarioFactory extends Factory
{
    protected $model = MovimientoInventario::class;

    public function definition(): array
    {
        $anteriores = fake()->numberBetween(0, 500);
        $cantidad = fake()->numberBetween(1, 10);

        return [
            'tipo' => 'venta',
            'cantidad' => -$cantidad,
            'existencias_anteriores' => $anteriores,
            'existencias_posteriores' => $anteriores - $cantidad,
            'notas' => fake()->sentence(),
        ];
    }
}
