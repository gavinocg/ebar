<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\MembresiaNegocio;
use App\Models\MovimientoInventario;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
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
        $negocio = Negocio::create(['nombre' => 'Bar I', 'identificador' => 'bar-i-' . str()->random(6), 'esta_activo' => true]);
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

    private function producto(Negocio $negocio, string $nombre, int $existencias = 10, bool $manejaExistencias = true, ?Sucursal $sucursal = null): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Comida ' . rand(1000, 9999), 'esta_activa' => true]);
        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => 5,
            'existencias' => $existencias,
            'maneja_existencias' => $manejaExistencias,
            'sucursal_id' => $sucursal?->id,
            'esta_activo' => true,
        ]);
    }

    public function test_un_ajuste_de_entrada_suma_existencias_y_registra_el_movimiento(): void
    {
        $admin = $this->propietario($this->negocio);
        $producto = $this->producto($this->negocio, 'Cerveza', 10);

        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad' => 5,
            'tipo' => 'entrada',
            'motivo' => 'Compra manual',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(15, (int) $producto->fresh()->existencias);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'usuario_id' => $admin->id,
            'tipo' => 'entrada',
            'cantidad' => 5,
            'existencias_anteriores' => 10,
            'existencias_posteriores' => 15,
            'tipo_referencia' => 'ajuste_manual',
            'notas' => 'Compra manual',
        ]);
    }

    public function test_un_ajuste_negativo_resta_existencias_y_registra_cantidad_negativa(): void
    {
        $admin = $this->propietario($this->negocio);
        $producto = $this->producto($this->negocio, 'Refresco', 10);

        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'tipo' => 'ajuste_negativo',
            'motivo' => 'Merma',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(7, (int) $producto->fresh()->existencias);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => 'ajuste_negativo',
            'cantidad' => -3,
            'existencias_anteriores' => 10,
            'existencias_posteriores' => 7,
        ]);
    }

    public function test_un_ajuste_no_puede_dejar_existencias_negativas(): void
    {
        $admin = $this->propietario($this->negocio);
        $producto = $this->producto($this->negocio, 'Snacks', 2);

        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad' => 5,
            'tipo' => 'ajuste_negativo',
            'motivo' => 'Merma',
        ])->assertSessionHasErrors('cantidad');

        $this->assertSame(2, (int) $producto->fresh()->existencias);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_no_se_ajusta_un_producto_sin_control_de_existencias(): void
    {
        $admin = $this->propietario($this->negocio);
        $producto = $this->producto($this->negocio, 'Servicio', 0, false);

        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad' => 5,
            'tipo' => 'entrada',
            'motivo' => 'Prueba',
        ])->assertSessionHasErrors('producto_id');

        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_no_se_ajusta_un_producto_de_otro_negocio(): void
    {
        $admin = $this->propietario($this->negocio);

        $otroBar = Negocio::create(['nombre' => 'Bar Ajeno', 'identificador' => 'bar-ajeno-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($otroBar->id);
        $productoAjeno = $this->producto($otroBar, 'Producto ajeno', 10);

        app(ContextoNegocio::class)->establecer($this->negocio->id);
        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), [
            'producto_id' => $productoAjeno->id,
            'cantidad' => 1,
            'tipo' => 'entrada',
            'motivo' => 'Prueba',
        ])->assertSessionHasErrors('producto_id');

        $this->assertSame(10, (int) $productoAjeno->fresh()->existencias);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_el_ajuste_requiere_cantidad_tipo_y_motivo_validos(): void
    {
        $admin = $this->propietario($this->negocio);
        $producto = $this->producto($this->negocio, 'Producto I', 10);

        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad' => 0,
            'tipo' => 'tipo_inexistente',
            'motivo' => '',
        ])->assertSessionHasErrors(['cantidad', 'tipo', 'motivo']);

        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_el_historial_lista_los_movimientos_y_filtra_por_tipo_y_producto(): void
    {
        $admin = $this->propietario($this->negocio);
        $cerveza = $this->producto($this->negocio, 'Cerveza', 10);
        $refresco = $this->producto($this->negocio, 'Refresco', 10);

        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), ['producto_id' => $cerveza->id, 'cantidad' => 5, 'tipo' => 'entrada', 'motivo' => 'Compra']);
        $this->post(route('inventario.ajustar'), ['producto_id' => $refresco->id, 'cantidad' => 2, 'tipo' => 'ajuste_negativo', 'motivo' => 'Merma']);

        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $cerveza->id, 'tipo' => 'entrada']);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $refresco->id, 'tipo' => 'ajuste_negativo']);

        $this->get(route('inventario.historial', ['tipo' => 'entrada']))->assertOk();
        $this->get(route('inventario.historial', ['producto_id' => $refresco->id]))->assertOk();
    }

    public function test_el_historial_filtra_por_sucursal(): void
    {
        $admin = $this->propietario($this->negocio);
        $sucursalA = Sucursal::create(['nombre' => 'Sucursal A']);
        $sucursalB = Sucursal::create(['nombre' => 'Sucursal B']);
        $productoA = $this->producto($this->negocio, 'Local A', 10, true, $sucursalA);
        $productoB = $this->producto($this->negocio, 'Local B', 10, true, $sucursalB);

        $this->actingAs($admin);
        $this->post(route('inventario.ajustar'), ['producto_id' => $productoA->id, 'cantidad' => 1, 'tipo' => 'entrada', 'motivo' => 'Compra']);
        $this->post(route('inventario.ajustar'), ['producto_id' => $productoB->id, 'cantidad' => 1, 'tipo' => 'entrada', 'motivo' => 'Compra']);

        $this->get(route('inventario.historial', ['sucursal_id' => $sucursalA->id]))->assertOk();
        $this->get(route('inventario.historial', ['sucursal_id' => $sucursalB->id]))->assertOk();
    }
}
