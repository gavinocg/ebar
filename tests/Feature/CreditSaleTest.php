<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria as Category;
use App\Models\Cliente;
use App\Models\ConfiguracionNegocio;
use App\Models\MovimientoEfectivo;
use App\Models\Negocio;
use App\Models\Producto as Product;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_venta_credito_sin_cliente_es_rechazada(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'credito',
            'pagado' => '0.00',
            'clave_idempotencia' => 'credito-sin-cliente',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_venta_credito_con_cliente_crea_venta_pendiente(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();
        $cliente = Cliente::create(['nombre' => 'Cliente Frecuente', 'esta_activo' => true]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'credito',
            'pagado' => '0.00',
            'clave_idempotencia' => 'credito-con-cliente',
            'cliente_id' => $cliente->id,
            'descripcion_cliente' => 'Cuenta mensual',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('pendiente', $venta->estado_cobro);
        $this->assertSame(0.0, (float) $venta->pagado);
        $this->assertSame(0.0, (float) $venta->cambio);
        $this->assertSame($cliente->id, $venta->cliente_id);
        $this->assertSame('Cliente Frecuente', $venta->nombre_cliente);
        $this->assertSame('Cuenta mensual', $venta->descripcion_cliente);
        $this->assertSame(0, MovimientoEfectivo::count());
    }

    public function test_venta_credito_sin_descripcion_es_rechazada(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();
        $cliente = Cliente::create(['nombre' => 'Cliente Frecuente', 'esta_activo' => true]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'credito',
            'pagado' => '0.00',
            'clave_idempotencia' => 'credito-sin-descripcion',
            'cliente_id' => $cliente->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('descripcion_cliente');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_venta_credito_con_cliente_de_otro_negocio_es_rechazada(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();

        $otroNegocio = Negocio::create([
            'nombre' => 'Otro negocio',
            'identificador' => 'otro-negocio',
            'esta_activo' => true,
        ]);
        $contextoGuardado = app(ContextoNegocio::class)->id();
        app(ContextoNegocio::class)->establecer($otroNegocio->id);
        $clienteAjeno = Cliente::create(['nombre' => 'Cliente Ajeno', 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($contextoGuardado);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'credito',
            'pagado' => '0.00',
            'clave_idempotencia' => 'credito-cliente-ajeno',
            'cliente_id' => $clienteAjeno->id,
            'descripcion_cliente' => 'Prueba',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_venta_credito_no_afecta_el_efectivo_esperado(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        $this->configuracion();
        $cliente = Cliente::create(['nombre' => 'Cliente Frecuente', 'esta_activo' => true]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'credito',
            'pagado' => '0.00',
            'clave_idempotencia' => 'credito-esperado',
            'cliente_id' => $cliente->id,
            'descripcion_cliente' => 'Cuenta mensual',
        ])->assertOk();

        $this->post(route('caja.cerrar'), [
            'es_final' => '1',
            'billetes' => [20 => 0, 100 => 0, 50 => 0, 10 => 0, 5 => 0, 1 => 0],
            'monedas' => [1 => 0, 0.50 => 0, 0.25 => 0, 0.10 => 0, 0.05 => 0, 0.01 => 0],
        ])->assertRedirect();

        $turno = TurnoCaja::where('usuario_id', $usuario->id)->first();
        $this->assertSame(0.0, (float) $turno->fresh()->efectivo_esperado);
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