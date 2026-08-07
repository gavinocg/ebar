<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categoria as Category;
use App\Models\Producto as Product;
use App\Models\Impresora as Printer;
use App\Models\Caja;
use App\Models\MembresiaNegocio;
use App\Services\ContextoNegocio;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $negocio = \App\Models\Negocio::firstOrFail();
        app(ContextoNegocio::class)->establecer($negocio->id);

        $planBasico = \App\Models\Plan::query()->updateOrCreate(
            ['nombre' => 'Básico'],
            ['descripcion' => 'Plan inicial para bares pequeños.', 'precio_mensual' => 10.00, 'limite_cajeros' => 2, 'limite_cajas' => 1, 'limite_sucursales' => 1],
        );

        $planPro = \App\Models\Plan::query()->updateOrCreate(
            ['nombre' => 'Pro'],
            ['descripcion' => 'Ideal para bares en crecimiento.', 'precio_mensual' => 25.00, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2],
        );

        \App\Models\Membresia::firstOrCreate(
            ['negocio_id' => $negocio->id],
            ['plan_id' => $planPro->id, 'estado' => 'activa', 'fecha_inicio' => now(), 'fecha_vencimiento' => now()->addYear()],
        );

        Caja::create(['nombre' => 'Caja principal', 'esta_activa' => true]);

        $bebidas = Category::create(['nombre' => 'Bebidas', 'descripcion' => 'Bebidas frías y calientes']);
        $alimentos = Category::create(['nombre' => 'Alimentos', 'descripcion' => 'Comida y snacks']);
        $postres = Category::create(['nombre' => 'Postres', 'descripcion' => 'Dulces y postres']);
        $otros = Category::create(['nombre' => 'Otros', 'descripcion' => 'Productos diversos']);

        Product::create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Coca-Cola 600ml',
            'descripcion' => 'Refresco de cola',
            'precio' => 25.00,
            'existencias' => 50,
            'codigo_barras' => '7501050345678',
        ]);

        Product::create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Agua Natural 500ml',
            'descripcion' => 'Agua purificada',
            'precio' => 15.00,
            'existencias' => 100,
            'codigo_barras' => '7501050345679',
        ]);

        Product::create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Café Americano',
            'descripcion' => 'Café caliente',
            'precio' => 35.00,
            'existencias' => 200,
        ]);

        Product::create([
            'categoria_id' => $alimentos->id,
            'nombre' => 'Sandwich Jamón',
            'descripcion' => 'Sandwich de jamón y queso',
            'precio' => 45.00,
            'existencias' => 20,
        ]);

        Product::create([
            'categoria_id' => $alimentos->id,
            'nombre' => 'Papas Fritas',
            'descripcion' => 'Papas fritas grandes',
            'precio' => 35.00,
            'existencias' => 30,
        ]);

        Product::create([
            'categoria_id' => $postres->id,
            'nombre' => 'Pastel Chocolate',
            'descripcion' => 'Rebanada de pastel',
            'precio' => 55.00,
            'existencias' => 15,
        ]);

        Product::create([
            'categoria_id' => $postres->id,
            'nombre' => 'Galletas',
            'descripcion' => 'Paquete de galletas',
            'precio' => 20.00,
            'existencias' => 40,
        ]);

        Product::create([
            'categoria_id' => $otros->id,
            'nombre' => 'Chicles',
            'descripcion' => 'Paquete de chicles',
            'precio' => 10.00,
            'existencias' => 80,
        ]);

        Printer::create([
            'nombre' => 'Impresora Principal',
            'tipo_conexion' => 'lan',
            'direccion' => '192.168.1.100',
            'puerto' => 9100,
            'ancho_papel' => '80mm',
            'esta_activa' => true,
            'es_predeterminada' => true,
        ]);

        $usuario = User::factory()->create([
            'nombre' => 'Administrador',
            'correo' => 'admin@ebar.com',
            'rol' => 'propietario',
        ]);

        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'propietario',
            'esta_activa' => true,
        ]);

        User::factory()->create([
            'nombre' => 'Super Administrador',
            'correo' => 'superadmin@ebar.com',
            'rol' => 'super_admin',
        ]);
    }
}
