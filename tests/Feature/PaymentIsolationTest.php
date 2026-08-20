<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ConfiguracionNegocio;
use App\Models\MembresiaNegocio;
use App\Models\MovimientoEfectivo;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\TurnoCajero;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function crearNegocioCompleto(string $nombre): array
    {
        $negocio = Negocio::create(['nombre' => $nombre, 'identificador' => str()->lower(str()->slug($nombre)) . '-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $admin = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $admin->id, 'rol' => 'propietario', 'esta_activa' => true]);

        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);


        $turno = TurnoCajero::create([
            'usuario_id' => $cajero->id,
            'negocio_id' => $negocio->id,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        $categoria = Categoria::create(['nombre' => 'Bebidas', 'esta_activa' => true]);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto Test',
            'precio' => 10,
            'existencias' => 100,
            'maneja_existencias' => true,
            'esta_activo' => true,
        ]);

        ConfiguracionNegocio::create(['nombre_negocio' => $nombre, 'cobrar_impuesto' => false, 'porcentaje_impuesto' => 0]);

        return [
            'negocio' => $negocio,
            'admin' => $admin,
            'cajero' => $cajero,
            'turno' => $turno,
            'producto' => $producto,
        ];
    }

    public function test_pago_efectivo_aislado_por_negocio(): void
    {
        $negocioA = $this->crearNegocioCompleto('Negocio A');
        $negocioB = $this->crearNegocioCompleto('Negocio B');

        $this->actingAs($negocioA['cajero']);
        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioA['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioA['producto']->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '20.00',
            'clave_idempotencia' => 'tenant-a-' . str()->random(10),
        ])->assertOk();

        $this->actingAs($negocioB['cajero']);
        app(ContextoNegocio::class)->establecer($negocioB['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioB['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioB['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'tenant-b-' . str()->random(10),
        ])->assertOk();

        $ventasA = Venta::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)->count();
        $ventasB = Venta::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)->count();

        $this->assertEquals(1, $ventasA);
        $this->assertEquals(1, $ventasB);

        $ventaA = Venta::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)->first();
        $ventaB = Venta::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)->first();

        $this->assertEquals($negocioA['negocio']->id, $ventaA->negocio_id);
        $this->assertEquals($negocioB['negocio']->id, $ventaB->negocio_id);
    }

    public function test_pago_credito_aislado_por_negocio(): void
    {
        $negocioA = $this->crearNegocioCompleto('Negocio Credito A');
        $negocioB = $this->crearNegocioCompleto('Negocio Credito B');

        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        $clienteA = Cliente::create([
            'nombre' => 'Cliente A',
            'descripcion' => 'Test',
            'esta_activo' => true,
        ]);

        app(ContextoNegocio::class)->establecer($negocioB['negocio']->id);
        $clienteB = Cliente::create([
            'nombre' => 'Cliente B',
            'descripcion' => 'Test',
            'esta_activo' => true,
        ]);

        $this->actingAs($negocioA['cajero']);
        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioA['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioA['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'credito',
            'cliente_id' => $clienteA->id,
            'pagado' => '0.00',
            'descripcion_cliente' => 'Venta credito A',
            'clave_idempotencia' => 'credito-a-' . str()->random(10),
        ])->assertOk();

        $this->actingAs($negocioB['cajero']);
        app(ContextoNegocio::class)->establecer($negocioB['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioB['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioB['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'credito',
            'cliente_id' => $clienteB->id,
            'pagado' => '0.00',
            'descripcion_cliente' => 'Venta credito B',
            'clave_idempotencia' => 'credito-b-' . str()->random(10),
        ])->assertOk();

        $ventasCreditoA = Venta::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)
            ->where('metodo_pago', 'credito')
            ->count();
        $ventasCreditoB = Venta::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)
            ->where('metodo_pago', 'credito')
            ->count();

        $this->assertEquals(1, $ventasCreditoA);
        $this->assertEquals(1, $ventasCreditoB);
    }

    public function test_pago_dividido_aislado_por_negocio(): void
    {
        $negocioA = $this->crearNegocioCompleto('Split A');
        $negocioB = $this->crearNegocioCompleto('Split B');

        $this->actingAs($negocioA['cajero']);
        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioA['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioA['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '10.00',
            'pagos_divididos' => [
                ['metodo' => 'efectivo', 'monto' => '5.00'],
                ['metodo' => 'transferencia', 'monto' => '5.00'],
            ],
            'clave_idempotencia' => 'split-a-' . str()->random(10),
        ])->assertOk();

        $this->actingAs($negocioB['cajero']);
        app(ContextoNegocio::class)->establecer($negocioB['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioB['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioB['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '10.00',
            'pagos_divididos' => [
                ['metodo' => 'efectivo', 'monto' => '3.00'],
                ['metodo' => 'transferencia', 'monto' => '7.00'],
            ],
            'clave_idempotencia' => 'split-b-' . str()->random(10),
        ])->assertOk();

        $ventasSplitA = Venta::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)
            ->where('metodo_pago', 'dividido')
            ->count();
        $ventasSplitB = Venta::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)
            ->where('metodo_pago', 'dividido')
            ->count();

        $this->assertEquals(1, $ventasSplitA);
        $this->assertEquals(1, $ventasSplitB);

        $ventaA = Venta::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)
            ->where('metodo_pago', 'dividido')
            ->first();
        $ventaB = Venta::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)
            ->where('metodo_pago', 'dividido')
            ->first();

        $this->assertEquals($negocioA['negocio']->id, $ventaA->negocio_id);
        $this->assertEquals($negocioB['negocio']->id, $ventaB->negocio_id);
    }

    public function test_idempotencia_aislada_por_negocio(): void
    {
        $negocioA = $this->crearNegocioCompleto('Idem A');
        $negocioB = $this->crearNegocioCompleto('Idem B');

        $claveA = 'clave-a-' . str()->random(8);
        $claveB = 'clave-b-' . str()->random(8);

        $this->actingAs($negocioA['cajero']);
        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioA['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioA['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => $claveA,
        ])->assertOk();

        // Segunda llamada con misma clave en MISMO negocio retorna la venta existente (idempotente, no duplica)
        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioA['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => $claveA,
        ])->assertOk();

        $this->actingAs($negocioB['cajero']);
        app(ContextoNegocio::class)->establecer($negocioB['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioB['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioB['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => $claveB,
        ])->assertOk();

        $ventasA = Venta::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)->count();
        $ventasB = Venta::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)->count();

        $this->assertEquals(1, $ventasA);
        $this->assertEquals(1, $ventasB);
    }

    public function test_turno_aislado_por_negocio(): void
    {
        $negocioA = $this->crearNegocioCompleto('Caja A');
        $negocioB = $this->crearNegocioCompleto('Caja B');

        $turnosA = TurnoCajero::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)->count();
        $turnosB = TurnoCajero::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)->count();

        $this->assertEquals(1, $turnosA);
        $this->assertEquals(1, $turnosB);

        $turnoA = TurnoCajero::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)->first();
        $turnoB = TurnoCajero::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)->first();

        $this->assertEquals($negocioA['negocio']->id, $turnoA->negocio_id);
        $this->assertEquals($negocioB['negocio']->id, $turnoB->negocio_id);
    }

    public function test_movimiento_efectivo_aislado_por_negocio(): void
    {
        $negocioA = $this->crearNegocioCompleto('Efectivo A');
        $negocioB = $this->crearNegocioCompleto('Efectivo B');

        $this->actingAs($negocioA['cajero']);
        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioA['turno']->id]);

        $this->post(route('caja.movimiento'), [
            'tipo' => 'entrada',
            'monto' => 50,
            'motivo' => 'Test entrada A',
        ])->assertRedirect();

        $this->actingAs($negocioB['cajero']);
        app(ContextoNegocio::class)->establecer($negocioB['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioB['turno']->id]);

        $this->post(route('caja.movimiento'), [
            'tipo' => 'retiro',
            'monto' => 25,
            'motivo' => 'Test retiro B',
        ])->assertRedirect();

        $movsA = MovimientoEfectivo::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)->count();
        $movsB = MovimientoEfectivo::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)->count();

        $this->assertEquals(1, $movsA); // solo entrada
        $this->assertEquals(1, $movsB); // solo retiro

        $movA = MovimientoEfectivo::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)
            ->where('tipo', 'entrada')
            ->first();
        $movB = MovimientoEfectivo::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)
            ->where('tipo', 'retiro')
            ->first();

        $this->assertEquals($negocioA['negocio']->id, $movA->negocio_id);
        $this->assertEquals($negocioB['negocio']->id, $movB->negocio_id);
    }

    public function test_cierre_turno_aislado_por_negocio(): void
    {
        $negocioA = $this->crearNegocioCompleto('Cierre A');
        $negocioB = $this->crearNegocioCompleto('Cierre B');

        MembresiaNegocio::where('negocio_id', $negocioA['negocio']->id)->update(['aprobacion_activa' => false]);
        MembresiaNegocio::where('negocio_id', $negocioB['negocio']->id)->update(['aprobacion_activa' => false]);

        $this->actingAs($negocioA['cajero']);
        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioA['turno']->id]);
        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioA['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'cierre-a-' . str()->random(10),
        ])->assertOk();

        $this->actingAs($negocioA['cajero']);
        $this->post(route('caja.cerrar'), [
            'es_final' => true,
            'billetes' => [10 => 1],
            'monedas' => [1 => 0],
            'notas' => 'Cierre A',
        ])->assertRedirect();

        $this->actingAs($negocioB['cajero']);
        app(ContextoNegocio::class)->establecer($negocioB['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioB['turno']->id]);
        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioB['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'cierre-b-' . str()->random(10),
        ])->assertOk();

        $this->actingAs($negocioB['cajero']);
        $this->post(route('caja.cerrar'), [
            'es_final' => true,
            'billetes' => [10 => 1],
            'monedas' => [1 => 0],
            'notas' => 'Cierre B',
        ])->assertRedirect();

        $turnosA = TurnoCajero::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)
            ->where('estado', 'aprobada')
            ->count();
        $turnosB = TurnoCajero::withoutGlobalScopes()->where('negocio_id', $negocioB['negocio']->id)
            ->where('estado', 'aprobada')
            ->count();

        $this->assertEquals(1, $turnosA);
        $this->assertEquals(1, $turnosB);
    }

    public function test_producto_no_se_mezcla_entre_negocios_en_venta(): void
    {
        $negocioA = $this->crearNegocioCompleto('Producto A');
        $negocioB = $this->crearNegocioCompleto('Producto B');

        $this->actingAs($negocioA['cajero']);
        app(ContextoNegocio::class)->establecer($negocioA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $negocioA['turno']->id]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $negocioA['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'prod-a-' . str()->random(10),
        ])->assertOk();

        $ventaA = Venta::withoutGlobalScopes()->where('negocio_id', $negocioA['negocio']->id)->first();
        $detalleA = $ventaA->detalles->first();

        $this->assertEquals($negocioA['producto']->id, $detalleA->producto_id);
        $this->assertEquals($negocioA['negocio']->id, $ventaA->negocio_id);
    }
}
