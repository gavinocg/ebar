<?php

namespace Tests\Feature;

use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Models\Categoria as Category;
use App\Models\Producto as Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_punto_de_venta_renderiza_su_interfaz(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('punto_venta.inicio'))->assertOk();
    }

    public function test_paginas_principales_renderizan(): void
    {
        $this->actingAs(User::factory()->create());
        $this->product();

        foreach ([
            'panel.inicio',
            'categorias.index',
            'productos.index',
            'ventas.index',
            'impresoras.index',
            'configuracion.negocio',
            'reportes.ventas',
            'reportes.inventario',
        ] as $nombreRuta) {
            $this->get(route($nombreRuta))->assertOk();
        }
    }

    public function test_checkout_rejects_invalid_quantities(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product();

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => -1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'invalid-quantity-test',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_checkout_is_integral_and_idempotent(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(price: 10, stock: 3);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $payload = [
            'items' => [['producto_id' => $product->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '25.00',
            'clave_idempotencia' => 'same-sale-test',
        ];

        $firstResponse = $this->postJson(route('punto_venta.cobrar'), $payload);
        $secondResponse = $this->postJson(route('punto_venta.cobrar'), $payload);

        $firstResponse->assertOk()->assertJsonPath('success', true);
        $secondResponse->assertOk()->assertJsonPath('sale.ticket_number', $firstResponse->json('sale.ticket_number'));
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $product->id,
            'tipo' => 'venta',
            'cantidad' => -2,
            'existencias_anteriores' => 3,
            'existencias_posteriores' => 1,
        ]);
        $this->assertDatabaseHas('productos', ['id' => $product->id, 'existencias' => 1]);
    }

    private function product(int $price = 10, int $stock = 10): Product
    {
        $category = Category::create(['nombre' => 'Pruebas']);

        return Product::create([
            'categoria_id' => $category->id,
            'nombre' => 'Producto de prueba',
            'precio' => $price,
            'existencias' => $stock,
            'esta_activo' => true,
        ]);
    }
}
