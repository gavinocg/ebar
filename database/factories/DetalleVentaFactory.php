<?php

namespace Database\Factories;

use App\Models\DetalleVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleVentaFactory extends Factory
{
    protected $model = DetalleVenta::class;

    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 5);
        $precio = fake()->randomFloat(2, 1, 50);
        $subtotal = round($precio * $cantidad, 2);

        return [
            'nombre_producto' => fake()->words(2, true),
            'cantidad' => $cantidad,
            'precio' => $precio,
            'descuento' => 0,
            'subtotal' => $subtotal,
        ];
    }
}
