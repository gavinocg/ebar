<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\MembresiaNegocio;
use App\Models\MovimientoInventario;
use App\Models\Negocio;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->negocio = $this->bar();
        app(ContextoNegocio::class)->establecer($this->negocio->id);
    }

    private function bar(): Negocio
    {
        $negocio = Negocio::create(['nombre' => 'Bar PO', 'identificador' => 'bar-po-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);


        return $negocio;
    }

    private function propietario(Negocio $negocio): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'propietario', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $usuario;
    }

    private function proveedor(Negocio $negocio, string $nombre = 'Proveedor Test'): Proveedor
    {
        return Proveedor::create([
            'nombre' => $nombre,
            'ruc' => '1234567890',
            'telefono' => '0999999999',
            'correo' => 'test@test.com',
            'direccion' => 'Dirección test',
        ]);
    }

    private function producto(Negocio $negocio, string $nombre, int $existencias = 0, bool $manejaExistencias = true): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Cat ' . rand(1000, 9999), 'esta_activa' => true]);
        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => 5,
            'existencias' => $existencias,
            'maneja_existencias' => $manejaExistencias,
            'esta_activo' => true,
        ]);
    }

    public function test_crear_orden_de_compra_exitosa_con_items(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);
        $producto1 = $this->producto($this->negocio, 'Cerveza', 10);
        $producto2 = $this->producto($this->negocio, 'Refresco', 5);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [
                ['producto_id' => $producto1->id, 'cantidad' => 20, 'precio_unitario' => 2.50],
                ['producto_id' => $producto2->id, 'cantidad' => 10, 'precio_unitario' => 3.00],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ordenes_compra', [
            'proveedor_id' => $proveedor->id,
            'estado' => 'pendiente',
        ]);
        $orden = OrdenCompra::first();
        $this->assertCount(2, $orden->detalles);
        $this->assertSame(80.0, (float) $orden->total); // 20*2.5 + 10*3.0 = 50 + 30 = 80
    }

    public function test_crear_orden_falla_si_faltan_items(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('ordenes_compra', 0);
    }

    public function test_crear_orden_falla_si_producto_no_pertenece_al_negocio(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);

        $otroBar = Negocio::create(['nombre' => 'Bar Ajeno', 'identificador' => 'bar-ajeno-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($otroBar->id);
        $productoAjeno = $this->producto($otroBar, 'Producto ajeno');

        app(ContextoNegocio::class)->establecer($this->negocio->id);
        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $productoAjeno->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ])->assertSessionHasErrors('items.0.producto_id');

        $this->assertDatabaseCount('ordenes_compra', 0);
    }

    public function test_recibir_orden_actualiza_stock_y_crea_movimiento_mercancias(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);
        $producto = $this->producto($this->negocio, 'Refresco', 5);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 15, 'precio_unitario' => 2]],
        ])->assertRedirect();

        $orden = OrdenCompra::first();

        $this->post(route('ordenes.recibir', $orden))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('recibida', $orden->fresh()->estado);
        $this->assertSame(20, (int) $producto->fresh()->existencias); // 5 + 15
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => 'mercancias',
            'cantidad' => 15,
            'existencias_anteriores' => 5,
            'existencias_posteriores' => 20,
            'tipo_referencia' => OrdenCompra::class,
            'id_referencia' => $orden->id,
        ]);
    }

    public function test_no_se_puede_recibir_orden_ya_recibida(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);
        $producto = $this->producto($this->negocio, 'Agua', 0);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 10, 'precio_unitario' => 1]],
        ]);
        $orden = OrdenCompra::first();

        $this->post(route('ordenes.recibir', $orden))->assertRedirect();
        $this->post(route('ordenes.recibir', $orden))->assertStatus(422);
        $this->assertSame('recibida', $orden->fresh()->estado);
    }

    public function test_no_se_puede_recibir_orden_con_producto_controlado_por_variantes(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);
        $producto = $this->producto($this->negocio, 'Con variantes', 10);
        ProductoVariante::create([
            'producto_id' => $producto->id,
            'nombre' => 'Grande',
            'precio' => 6,
            'stock' => 8,
            'esta_activo' => true,
        ]);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio_unitario' => 2]],
        ]);
        $orden = OrdenCompra::first();

        $this->post(route('ordenes.recibir', $orden))->assertStatus(422);
        $this->assertSame(10, (int) $producto->fresh()->existencias);
    }

    public function test_producto_sin_maneja_existencias_no_afecta_stock_al_recibir(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);
        $producto = $this->producto($this->negocio, 'Servicio', 999, false);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 10, 'precio_unitario' => 1]],
        ]);
        $orden = OrdenCompra::first();

        $this->post(route('ordenes.recibir', $orden))->assertRedirect();

        $this->assertSame(999, (int) $producto->fresh()->existencias);
        $this->assertDatabaseMissing('movimientos_inventario', ['producto_id' => $producto->id]);
    }

    public function test_no_se_puede_eliminar_orden_recibida(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);
        $producto = $this->producto($this->negocio, 'Eliminar', 0);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ]);
        $orden = OrdenCompra::first();

        $this->post(route('ordenes.recibir', $orden));
        $this->delete(route('ordenes.destroy', $orden))->assertSessionHasErrors('orden');
        $this->assertDatabaseHas('ordenes_compra', ['id' => $orden->id]);
    }

    public function test_se_puede_eliminar_orden_pendiente(): void
    {
        $admin = $this->propietario($this->negocio);
        $proveedor = $this->proveedor($this->negocio);
        $producto = $this->producto($this->negocio, 'Eliminar pendiente', 0);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ]);
        $orden = OrdenCompra::first();

        $this->delete(route('ordenes.destroy', $orden))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('ordenes_compra', ['id' => $orden->id]);
    }

    public function test_numeracion_ordenes_global_consecutiva_entre_negocios(): void
    {
        $negocio1 = $this->negocio; // ya creado en setUp
        $admin1 = $this->propietario($negocio1);
        $proveedor1 = $this->proveedor($negocio1);
        $producto1 = $this->producto($negocio1, 'P1', 0);

        $this->actingAs($admin1);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor1->id,
            'items' => [['producto_id' => $producto1->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ]);
        $orden1 = OrdenCompra::first();

        $negocio2 = Negocio::create(['nombre' => 'Bar 2', 'identificador' => 'bar-2-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio2->id);
        $admin2 = $this->propietario($negocio2);
        $proveedor2 = $this->proveedor($negocio2, 'Prov 2');
        $producto2 = $this->producto($negocio2, 'P2', 0);

        $this->actingAs($admin2);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor2->id,
            'items' => [['producto_id' => $producto2->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ]);
        $orden2 = OrdenCompra::latest('id')->first();

        $this->assertSame(['OC-00001', 'OC-00002'], OrdenCompra::withoutGlobalScopes()->orderBy('id')->pluck('numero')->all());
    }
}
