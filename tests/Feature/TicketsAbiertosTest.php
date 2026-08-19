<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\ConfiguracionNegocio;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Sucursal;
use App\Models\TicketAbierto;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketsAbiertosTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_requiere_un_turno_de_caja_abierto(): void
    {
        [$bar, $cajero, $turno] = $this->setupBar();
        $turno->update(['estado' => 'cerrada']);
        $producto = $this->producto();

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => null]);

        $this->postJson(route('tickets_abiertos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('tickets_abiertos', 0);
    }

    public function test_store_usa_el_precio_de_la_base_de_datos_y_clampa_el_descuento(): void
    {
        [$bar, $cajero, $turno] = $this->setupBar();
        $producto = $this->producto(5, 10);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => null]);

        $this->postJson(route('tickets_abiertos.store'), [
            'nombre' => 'Mesa 3',
            'items' => [[
                'producto_id' => $producto->id,
                'cantidad' => 2,
                'precio' => 999,
                'descuento' => 999,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $detalle = TicketAbierto::where('turno_caja_id', $turno->id)->firstOrFail()->detalles()->firstOrFail();

        $this->assertSame(5.0, (float) $detalle->precio);
        $this->assertSame(10.0, (float) $detalle->descuento);
        $this->assertSame(0.0, (float) $detalle->subtotal);
        $this->assertSame('Producto de prueba', $detalle->nombre_producto);
    }

    public function test_store_rechaza_existencias_insuficientes(): void
    {
        [$bar, $cajero] = $this->setupBar();
        $producto = $this->producto(5, 1);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => null]);

        $this->postJson(route('tickets_abiertos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('tickets_abiertos', 0);
    }

    public function test_store_rechaza_una_variante_que_no_pertenece_al_producto(): void
    {
        [$bar, $cajero] = $this->setupBar();
        $producto = $this->producto();
        $otro = $this->producto();
        $variante = ProductoVariante::create([
            'producto_id' => $otro->id,
            'nombre' => 'Grande',
            'precio' => 6,
            'stock' => 10,
            'esta_activo' => true,
        ]);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => null]);

        $this->postJson(route('tickets_abiertos.store'), [
            'items' => [['producto_id' => $producto->id, 'producto_variante_id' => $variante->id, 'cantidad' => 1]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('tickets_abiertos', 0);
    }

    public function test_index_solo_muestra_los_tickets_del_turno_del_cajero(): void
    {
        [$bar, $cajeroUno, $turnoUno] = $this->setupBar();
        $this->producto();

        $cajeroDos = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $bar->id, 'usuario_id' => $cajeroDos->id, 'rol' => 'cajero', 'esta_activa' => true]);
        $turnoDos = TurnoCaja::create([
            'caja_id' => $turnoUno->caja_id,
            'usuario_id' => $cajeroDos->id,
            'fondo_inicial' => 50,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        $ticketUno = TicketAbierto::create(['negocio_id' => $bar->id, 'turno_caja_id' => $turnoUno->id, 'usuario_id' => $cajeroUno->id, 'nombre' => 'Mesa 1']);
        TicketAbierto::create(['negocio_id' => $bar->id, 'turno_caja_id' => $turnoDos->id, 'usuario_id' => $cajeroDos->id, 'nombre' => 'Mesa 2']);

        $this->actingAs($cajeroUno)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => null]);
        $this->getJson(route('tickets_abiertos.index'))->assertOk()->assertJsonCount(1)->assertJsonFragment(['id' => $ticketUno->id]);

        $this->actingAs($cajeroDos)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => null]);
        $this->getJson(route('tickets_abiertos.index'))->assertOk()->assertJsonCount(1);
        $this->assertSame($turnoDos->id, (int) TicketAbierto::find(2)->turno_caja_id);
    }

    public function test_abrir_turno_rechaza_una_caja_ya_ocupada_por_otro_cajero(): void
    {
        [$bar, $cajeroUno, $turnoUno] = $this->setupBar();
        $turnoUno->update(['estado' => 'cerrada']);
        $caja = Caja::create(['nombre' => 'Caja Única', 'esta_activa' => true]);

        $this->actingAs($cajeroUno)->withSession(['negocio_id' => $bar->id]);
        $this->post(route('caja.abrir'), ['fondo_inicial' => 100, 'caja_id' => $caja->id])->assertRedirect();

        $cajeroDos = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $bar->id, 'usuario_id' => $cajeroDos->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $this->actingAs($cajeroDos)->withSession(['negocio_id' => $bar->id]);
        $this->post(route('caja.abrir'), ['fondo_inicial' => 100, 'caja_id' => $caja->id])
            ->assertSessionHasErrors('caja');

        $this->assertSame(1, TurnoCaja::where('estado', 'abierta')->count());
    }

    public function test_abrir_turno_rechaza_una_caja_de_otra_sucursal_si_el_cajero_esta_asignado(): void
    {
        [$bar, $cajero] = $this->setupBar();

        $sucursalAsignada = Sucursal::create(['nombre' => 'Centro', 'esta_activa' => true]);
        $sucursalOtra = Sucursal::create(['nombre' => 'Norte', 'esta_activa' => true]);
        $cajaOtra = Caja::create(['nombre' => 'Caja Norte', 'sucursal_id' => $sucursalOtra->id, 'esta_activa' => true]);

        $cajero->membresias()->first()->update(['sucursal_id' => $sucursalAsignada->id]);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => $sucursalAsignada->id]);
        $this->post(route('caja.abrir'), ['fondo_inicial' => 100, 'caja_id' => $cajaOtra->id])
            ->assertSessionHasErrors('caja');
    }

    public function test_solicitar_modificacion_no_se_apila(): void
    {
        [$bar, $cajero, $turno] = $this->setupBar();
        $turno->update(['estado' => 'cerrada']);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id]);

        $this->post(route('cuadres.solicitar-modificacion', $turno), ['motivo' => 'Faltó una venta'])->assertRedirect();
        $this->assertSame('pendiente_modificacion', $turno->fresh()->estado);

        $this->post(route('cuadres.solicitar-modificacion', $turno), ['motivo' => 'De nuevo'])->assertStatus(422);
    }

    public function test_cajero_asignado_a_una_sucursal_no_puede_cambiar_a_otra(): void
    {
        [$bar, $cajero] = $this->setupBar();

        $sucursalAsignada = Sucursal::create(['nombre' => 'Centro', 'esta_activa' => true]);
        $sucursalOtra = Sucursal::create(['nombre' => 'Norte', 'esta_activa' => true]);

        $cajero->membresias()->first()->update(['sucursal_id' => $sucursalAsignada->id]);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id]);

        $this->post(route('negocio.sucursal.cambiar'), ['sucursal_id' => $sucursalOtra->id])
            ->assertSessionHasErrors('sucursal_id');

        $this->post(route('negocio.sucursal.cambiar'), ['sucursal_id' => $sucursalAsignada->id])
            ->assertRedirect(route('punto_venta.inicio'));
    }

    public function test_el_numero_de_comprobante_se_genera_desde_el_insert_sin_estado_pending(): void
    {
        [$bar, $cajero, $turno] = $this->setupBar();
        $producto = $this->producto(10, 10);
        ConfiguracionNegocio::create([
            'nombre_negocio' => 'Bar P',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
            'descuento_activo' => false,
        ]);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id, 'sucursal_id' => null]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'numeracion-fase-p',
        ]);

        $response->assertOk()->assertJsonPath('sale.numero_comprobante', 'CMP-000001');
        $this->assertDatabaseHas('ventas', ['turno_caja_id' => $turno->id, 'numero_comprobante' => 'CMP-000001']);
    }

    private function setupBar(): array
    {
        $bar = Negocio::create(['nombre' => 'Bar P', 'identificador' => 'bar-p-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($bar->id);

        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $bar->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $caja = Caja::create(['nombre' => 'Caja P', 'esta_activa' => true]);
        $turno = TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        return [$bar, $cajero, $turno];
    }

    private function producto(int $precio = 5, int $stock = 10): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Comida']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto de prueba',
            'precio' => $precio,
            'existencias' => $stock,
            'esta_activo' => true,
        ]);
    }
}