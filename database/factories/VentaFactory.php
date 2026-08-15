<?php

namespace Database\Factories;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5, 200);
        $impuesto = fake()->randomFloat(2, 0, 20);
        $total = round($subtotal + $impuesto, 2);

        return [
            'numero_comprobante' => 'CMP-' . str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'clave_idempotencia' => Str::uuid()->toString(),
            'subtotal' => $subtotal,
            'descuento' => 0,
            'impuesto' => $impuesto,
            'impuesto_habilitado' => false,
            'porcentaje_impuesto' => 0,
            'total' => $total,
            'metodo_pago' => 'efectivo',
            'pagado' => $total,
            'cambio' => 0,
            'estado_cobro' => 'pagado',
        ];
    }
}
