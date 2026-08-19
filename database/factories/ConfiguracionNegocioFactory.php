<?php

namespace Database\Factories;

use App\Models\ConfiguracionNegocio;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfiguracionNegocioFactory extends Factory
{
    protected $model = ConfiguracionNegocio::class;

    public function definition(): array
    {
        return [
            'nombre_negocio' => fake()->company(),
            'telefono' => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'mensaje_comprobante' => '¡GRACIAS POR SU COMPRA!',
            'cobrar_impuesto' => false,
            'descuento_activo' => false,
            'porcentaje_impuesto' => 15.00,
        ];
    }
}
