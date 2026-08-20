<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ConfiguracionNegocio;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\TurnoCajero;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicioReembolsoStockTest extends TestCase
{
    use RefreshDatabase;

    private function setupTenant(): array
    {
        $negocio = Negocio::create([
            'nombre' => 'Bar Test',
            'identificador' => 'bar-reembolso-' . str()->random(6),
            'esta_activo' => true,
        ]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $cajero = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $cajero->id,
            'rol' => 'cajero',
            'esta_activa' => true,
        ]);

        $admin = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $admin->id,
            'rol' => 'propietario',
            'esta_activa' => true,
        ]);

        $turno = TurnoCajero::create([
            'usuario_id' => $cajero->id,
            'negocio_id' => $negocio->id,
            'sucursal_id' => null,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        ConfiguracionNegocio::create([
            'nombre_negocio' => 'Bar Test',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        return compact('negocio', 'cajero', 'admin', 'turno');
    }

    public function test_reembolso_restaura_stock_de_la_variante_y_no_toca_el_padre(): void
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

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'variante_id' => $variante->id]],
            'metodo_pago' => 'efectivo',
            'pagado' => '20.00',
            'clave_idempotencia' => 'reembolso-variante-' . str()->random(8),
        ])->assertOk();

        $this->assertSame(7, (int) $variante->fresh()->stock);
        $this->assertSame(50, (int) $producto->fresh()->existencias);

        $venta = Venta::first();
        $detalle = $venta->detalles->first();

        $this->actingAs($tenant['admin']);
        app(ContextoNegocio::class)->establecer($tenant['negocio']->id);

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Devolución de la variante',
            'metodo' => 'credito',
            'items' => [$detalle->id => 3],
        ])->assertRedirect();

        $this->assertSame(10, (int) $variante->fresh()->stock, 'El stock de la variante debe restaurarse al reembolsar.');
        $this->assertSame(50, (int) $producto->fresh()->existencias, 'El stock del producto padre no debe cambiar cuando la variante tiene stock propio.');
    }

    public function test_reembolso_registra_movimiento_de_inventario_de_la_variante(): void
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
            'stock' => 5,
            'esta_activo' => true,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'variante_id' => $variante->id]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'reembolso-mov-' . str()->random(8),
        ])->assertOk();

        $venta = Venta::first();
        $detalle = $venta->detalles->first();

        $this->actingAs($tenant['admin']);
        app(ContextoNegocio::class)->establecer($tenant['negocio']->id);

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Devolución variante',
            'metodo' => 'credito',
            'items' => [$detalle->id => 2],
        ])->assertRedirect();

        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => 'devolucion',
            'cantidad' => 2,
        ]);
    }
}
