<?php

namespace Database\Factories;

use App\Models\Impresora;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImpresoraFactory extends Factory
{
    protected $model = Impresora::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Impresora ' . fake()->randomElement(['Cocina', 'Barra', 'Comedor', 'Caja']),
            'tipo_conexion' => 'bluetooth',
            'ancho_papel' => '58mm',
            'esta_activa' => true,
            'es_predeterminada' => false,
        ];
    }
}
