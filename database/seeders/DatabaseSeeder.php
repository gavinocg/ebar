<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Printer;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $bebidas = Category::create(['name' => 'Bebidas', 'description' => 'Bebidas frías y calientes']);
        $alimentos = Category::create(['name' => 'Alimentos', 'description' => 'Comida y snacks']);
        $postres = Category::create(['name' => 'Postres', 'description' => 'Dulces y postres']);
        $otros = Category::create(['name' => 'Otros', 'description' => 'Productos diversos']);

        Product::create([
            'category_id' => $bebidas->id,
            'name' => 'Coca-Cola 600ml',
            'description' => 'Refresco de cola',
            'price' => 25.00,
            'stock' => 50,
            'barcode' => '7501050345678',
        ]);

        Product::create([
            'category_id' => $bebidas->id,
            'name' => 'Agua Natural 500ml',
            'description' => 'Agua purificada',
            'price' => 15.00,
            'stock' => 100,
            'barcode' => '7501050345679',
        ]);

        Product::create([
            'category_id' => $bebidas->id,
            'name' => 'Café Americano',
            'description' => 'Café caliente',
            'price' => 35.00,
            'stock' => 200,
        ]);

        Product::create([
            'category_id' => $alimentos->id,
            'name' => 'Sandwich Jamón',
            'description' => 'Sandwich de jamón y queso',
            'price' => 45.00,
            'stock' => 20,
        ]);

        Product::create([
            'category_id' => $alimentos->id,
            'name' => 'Papas Fritas',
            'description' => 'Papas fritas grandes',
            'price' => 35.00,
            'stock' => 30,
        ]);

        Product::create([
            'category_id' => $postres->id,
            'name' => 'Pastel Chocolate',
            'description' => 'Rebanada de pastel',
            'price' => 55.00,
            'stock' => 15,
        ]);

        Product::create([
            'category_id' => $postres->id,
            'name' => 'Galletas',
            'description' => 'Paquete de galletas',
            'price' => 20.00,
            'stock' => 40,
        ]);

        Product::create([
            'category_id' => $otros->id,
            'name' => 'Chicles',
            'description' => 'Paquete de chicles',
            'price' => 10.00,
            'stock' => 80,
        ]);

        Printer::create([
            'name' => 'Impresora Principal',
            'connection_type' => 'lan',
            'address' => '192.168.1.100',
            'port' => 9100,
            'paper_width' => '80mm',
            'is_active' => true,
            'is_default' => true,
        ]);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@ebar.com',
        ]);
    }
}
