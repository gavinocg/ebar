<?php

namespace Tests\Feature;

use App\Models\Categoria as Category;
use App\Models\ConfiguracionNegocio;
use App\Models\MovimientoEfectivo;
use App\Models\Negocio;
use App\Models\Producto as Product;
use App\Models\TurnoCajero;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pago_dividido_exacto_crea_venta_con_partes_y_movimientos(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '0.00',
            'clave_idempotencia' => 'dividido-exacto',
            'pagos_divididos' => [
                ['metodo' => 'efectivo', 'monto' => '6.00'],
                ['metodo' => 'transferencia', 'monto' => '4.00'],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('ventas', 1);
        $venta = Venta::first();
        $this->assertSame('dividido', $venta->metodo_pago);
        $this->assertSame('pagado', $venta->estado_cobro);
        $this->assertEquals(10.0, (float) $venta->pagado);
        $this->assertEquals(0.0, (float) $venta->cambio);
        $this->assertCount(2, $venta->pagos_divididos);
        $this->assertSame('efectivo', $venta->pagos_divididos[0]['metodo']);
        $this->assertEquals(6.0, (float) $venta->pagos_divididos[0]['monto']);
        $this->assertSame('transferencia', $venta->pagos_divididos[1]['metodo']);
        $this->assertEquals(4.0, (float) $venta->pagos_divididos[1]['monto']);

        $this->assertSame(2, MovimientoEfectivo::count());
        $this->assertSame(6.0, (float) MovimientoEfectivo::where('tipo', 'venta')->value('monto'));
        $this->assertSame(4.0, (float) MovimientoEfectivo::where('tipo', 'transferencia')->value('monto'));

        $this->assertSame(9, (int) $producto->fresh()->existencias);
    }

    public function test_pago_dividido_con_suma_insuficiente_se_rechaza(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '0.00',
            'clave_idempotencia' => 'dividido-insuficiente',
            'pagos_divididos' => [
                ['metodo' => 'efectivo', 'monto' => '5.00'],
                ['metodo' => 'transferencia', 'monto' => '4.00'],
            ],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(0, MovimientoEfectivo::count());
        $this->assertSame(10, (int) $producto->fresh()->existencias);
    }

    public function test_pago_dividido_con_sobrepago_se_rechaza_sin_cambio_fantasma(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '0.00',
            'clave_idempotencia' => 'dividido-sobrepago',
            'pagos_divididos' => [
                ['metodo' => 'efectivo', 'monto' => '6.00'],
                ['metodo' => 'transferencia', 'monto' => '5.00'],
            ],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(0, MovimientoEfectivo::count());
    }

    public function test_pago_dividido_con_parte_de_metodo_no_permitido_se_rechaza(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '0.00',
            'clave_idempotencia' => 'dividido-metodo-invalido',
            'pagos_divididos' => [
                ['metodo' => 'credito', 'monto' => '10.00'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('pagos_divididos.0.metodo');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_pago_dividido_sin_partes_se_rechaza(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '0.00',
            'clave_idempotencia' => 'dividido-sin-partes',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('pagos_divididos');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_efectivo_esperado_incluye_solo_la_parte_efectiva_del_dividido(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '0.00',
            'clave_idempotencia' => 'dividido-cierre',
            'pagos_divididos' => [
                ['metodo' => 'efectivo', 'monto' => '6.00'],
                ['metodo' => 'transferencia', 'monto' => '4.00'],
            ],
        ])->assertOk();

        $this->post(route('caja.cerrar'), [
            'es_final' => '1',
            'billetes' => [20 => 0, 100 => 0, 50 => 0, 10 => 0, 5 => 0, 1 => 0],
            'monedas' => [1 => 0, 0.50 => 0, 0.25 => 0, 0.10 => 0, 0.05 => 0, 0.01 => 0],
        ])->assertRedirect();

        $turno = TurnoCajero::where('usuario_id', $usuario->id)->first();
        $this->assertSame(6.0, (float) $turno->fresh()->efectivo_esperado);
    }

    public function test_idempotencia_no_duplica_venta_dividida(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $payload = [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'dividido',
            'pagado' => '0.00',
            'clave_idempotencia' => 'dividido-reintento',
            'pagos_divididos' => [
                ['metodo' => 'efectivo', 'monto' => '6.00'],
                ['metodo' => 'transferencia', 'monto' => '4.00'],
            ],
        ];

        $this->postJson(route('punto_venta.cobrar'), $payload)->assertOk();
        $this->postJson(route('punto_venta.cobrar'), $payload)->assertOk();

        $this->assertDatabaseCount('ventas', 1);
        $this->assertSame(2, MovimientoEfectivo::count());
        $this->assertSame(9, (int) $producto->fresh()->existencias);
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

    private function configuracion(): ConfiguracionNegocio
    {
        return ConfiguracionNegocio::create([
            'nombre_negocio' => 'Negocio principal',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);
    }

    private function cajero(): User
    {
        $negocio = Negocio::firstOrCreate(
            ['identificador' => 'negocio-principal'],
            ['nombre' => 'Negocio principal', 'esta_activo' => true],
        );
        app(ContextoNegocio::class)->establecer($negocio->id);

        $usuario = User::factory()->create();
        \App\Models\MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'cajero',
            'esta_activa' => true,
        ]);

        return $usuario;
    }

    private function abrirTurno(User $usuario): TurnoCajero
    {

        return TurnoCajero::create([
            'usuario_id' => $usuario->id,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);
    }
}
