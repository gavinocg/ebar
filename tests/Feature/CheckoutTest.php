<?php

namespace Tests\Feature;

use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Models\Categoria as Category;
use App\Models\Producto as Product;
use App\Models\Caja;
use App\Models\TurnoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
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
        $secondResponse->assertOk()->assertJsonPath('sale.numero_comprobante', $firstResponse->json('sale.numero_comprobante'));
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

    public function test_caja_se_puede_abrir_y_cerrar_con_arqueo(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        Caja::create(['nombre' => 'Caja de pruebas', 'esta_activa' => true]);

        $this->post(route('caja.abrir'), ['fondo_inicial' => '100.00'])
            ->assertRedirect();
        $this->assertDatabaseHas('turnos_caja', [
            'usuario_id' => $usuario->id,
            'estado' => 'abierta',
            'fondo_inicial' => 100,
        ]);

        $this->post(route('caja.cerrar'), ['efectivo_contado' => '100.00'])
            ->assertRedirect();
        $this->assertDatabaseHas('turnos_caja', [
            'usuario_id' => $usuario->id,
            'estado' => 'cerrada',
            'efectivo_esperado' => 100,
            'diferencia' => 0,
        ]);
    }

    public function test_producto_y_categoria_aceptan_recursos_visuales(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $categoriaResponse = $this->post(route('categorias.store'), [
            'nombre' => 'Bebidas',
            'descripcion' => 'Bebidas frías',
            'color' => '#2563eb',
            'icono' => 'bi bi-cup-straw',
            'orden' => 1,
            'esta_activa' => 1,
            'imagen' => UploadedFile::fake()->image('bebidas.webp', 500, 500),
        ]);
        $categoriaResponse->assertRedirect();
        $categoria = Category::firstOrFail();

        $productoResponse = $this->post(route('productos.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Jugo natural',
            'precio' => 2.50,
            'existencias' => 20,
            'color' => '#fef3c7',
            'distintivo' => 'Nuevo',
            'distintivo_color' => '#16a34a',
            'destacado' => 1,
            'esta_activo' => 1,
            'imagen' => UploadedFile::fake()->image('jugo.png', 500, 500),
        ]);
        $productoResponse->assertRedirect();

        $producto = Product::firstOrFail();
        $this->assertNotEmpty($categoria->fresh()->imagen_path);
        $this->assertNotEmpty($producto->imagen_path);
        $this->assertDatabaseHas('productos', [
            'distintivo' => 'Nuevo',
            'destacado' => 1,
            'color' => '#fef3c7',
        ]);
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

    private function abrirTurno(User $usuario): TurnoCaja
    {
        $caja = Caja::create(['nombre' => 'Caja de pruebas', 'esta_activa' => true]);

        return TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $usuario->id,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);
    }
}
