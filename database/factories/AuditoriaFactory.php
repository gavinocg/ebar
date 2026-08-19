<?php

namespace Database\Factories;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditoriaFactory extends Factory
{
    protected $model = Auditoria::class;

    public function definition(): array
    {
        return [
            'modulo' => fake()->randomElement(['productos', 'ventas', 'cajeros', 'roles', 'configuracion']),
            'accion' => fake()->randomElement(['crear', 'actualizar', 'eliminar']),
            'descripcion' => fake()->sentence(),
            'detalles' => ['antes' => fake()->word(), 'despues' => fake()->word()],
            'direccion_ip' => fake()->ipv4(),
        ];
    }
}
