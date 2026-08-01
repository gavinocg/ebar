<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categoria as Category;
use App\Models\Producto as Product;
use App\Models\Impresora as Printer;
use App\Models\Caja;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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

        User::factory()->create([
            'nombre' => 'Administrador',
            'correo' => 'admin@ebar.com',
            'rol' => 'administrador',
        ]);
    }
}
