<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\ProductoVariante;
use App\Models\Negocio;
use App\Models\TurnoCajero;
use App\Models\ConfiguracionNegocio;
use App\Models\User;
use App\Models\MembresiaNegocio;
use App\Services\ContextoNegocio;
use App\Services\ServicioCobro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private function setupTenant(): array
    {
        $negocio = Negocio::create(['nombre' => 'Bar Test', 'identificador' => 'bar-test-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $turno = TurnoCajero::create([
            'usuario_id' => $cajero->id,
            'negocio_id' => $negocio->id,
            'sucursal_id' => null,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        ConfiguracionNegocio::create(['nombre_negocio' => 'Bar Test', 'cobrar_impuesto' => false, 'porcentaje_impuesto' => 0]);

        return compact('negocio', 'cajero', 'turno');
    }

    public function test_variante_con_stock_proprio_no_decrementa_padre(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Cerveza',
            'precio' => 3.00,
            'existencias' => 50,
            'esta_activo' => true,
        ]);

        $variante = ProductoVariante::create([
            'negocio_id' => $tenant['negocio']->id,
            'producto_id' => $producto->id,
            'nombre' => 'Lite',
            'precio' => 3.50,
            'stock' => 10,
            'esta_activo' => true,
        ]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'variante_id' => $variante->id]],
            'metodo_pago' => 'efectivo',
            'pagado' => '15.00',
            'clave_idempotencia' => 'test-stock-variant',
        ]);

        $response->assertOk();

        $producto->refresh();
        $variante->refresh();

        $this->assertEquals(50, $producto->existencias, 'Parent product stock should NOT be decremented when variant has its own stock');
        $this->assertEquals(7, $variante->stock, 'Variant stock should be decremented');
    }

    public function test_producto_sin_variante_si_decrementa_existencias(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Cerveza',
            'precio' => 3.00,
            'existencias' => 50,
            'esta_activo' => true,
        ]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'test-stock-parent',
        ]);

        $response->assertOk();

        $producto->refresh();
        $this->assertEquals(48, $producto->existencias);
    }

    public function test_variante_sin_stock_propio_decrementa_padre(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Cerveza',
            'precio' => 3.00,
            'existencias' => 50,
            'esta_activo' => true,
        ]);

        $variante = ProductoVariante::create([
            'negocio_id' => $tenant['negocio']->id,
            'producto_id' => $producto->id,
            'nombre' => 'Lite',
            'precio' => 3.50,
            'stock' => null,
            'esta_activo' => true,
        ]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'variante_id' => $variante->id]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'test-stock-variant-null',
        ]);

        $response->assertOk();

        $producto->refresh();
        $this->assertEquals(48, $producto->existencias, 'Parent product stock should be decremented when variant has null stock');
    }
}
