<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'ruc' => fake()->numerify('##########'),
            'telefono' => fake()->phoneNumber(),
            'correo' => fake()->safeEmail(),
            'direccion' => fake()->address(),
            'esta_activo' => true,
        ];
    }
}
