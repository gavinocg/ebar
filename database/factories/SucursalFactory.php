<?php

namespace Database\Factories;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

class SucursalFactory extends Factory
{
    protected $model = Sucursal::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->city(),
            'direccion' => fake()->address(),
            'telefono' => fake()->phoneNumber(),
            'esta_activa' => true,
        ];
    }
}
