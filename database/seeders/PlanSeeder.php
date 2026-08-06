<?php

namespace Database\Seeders;

use App\Models\Membresia;
use App\Models\Negocio;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $basico = Plan::firstOrCreate(
            ['nombre' => 'Básico'],
            ['descripcion' => 'Plan inicial para bares pequeños.', 'precio_mensual' => 10.00, 'duracion_dias' => 30, 'limite_cajeros' => 2, 'limite_cajas' => 1, 'limite_sucursales' => 1],
        );

        $pro = Plan::firstOrCreate(
            ['nombre' => 'Pro'],
            ['descripcion' => 'Ideal para bares en crecimiento.', 'precio_mensual' => 25.00, 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2],
        );

        $negocio = Negocio::first();
        if ($negocio && !Membresia::where('negocio_id', $negocio->id)->exists()) {
            Membresia::create([
                'negocio_id' => $negocio->id,
                'plan_id' => $pro->id,
                'estado' => 'activa',
                'fecha_inicio' => now(),
                'fecha_vencimiento' => now()->addYear(),
            ]);
        }
    }
}