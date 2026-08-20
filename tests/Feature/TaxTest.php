<?php

namespace Tests\Feature;

use App\Models\Categoria as Category;
use App\Models\ConfiguracionNegocio;
use App\Models\Negocio;
use App\Models\Producto as Product;
use App\Models\TurnoCajero;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_venta_con_impuesto_calcula_y_snapshotea_impuesto_y_porcentaje(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        ConfiguracionNegocio::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => true,
            'porcentaje_impuesto' => 15,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '23.00',
            'clave_idempotencia' => 'tax-venta',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('20.00', number_format($venta->subtotal, 2));
        $this->assertSame('3.00', number_format($venta->impuesto, 2));
        $this->assertSame('23.00', number_format($venta->total, 2));
        $this->assertTrue($venta->impuesto_habilitado);
        $this->assertSame('15.00', number_format((float) $venta->porcentaje_impuesto, 2));
    }

    public function test_venta_con_impuesto_desactivado_no_aplica_impuesto(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        ConfiguracionNegocio::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 15,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'tax-sin-impuesto',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('10.00', number_format($venta->total, 2));
        $this->assertSame('0.00', number_format($venta->impuesto, 2));
        $this->assertFalse($venta->impuesto_habilitado);
        $this->assertSame('0.00', number_format((float) $venta->porcentaje_impuesto, 2));
    }

    public function test_cambio_de_porcentaje_afecta_nuevas_ventas(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $producto = $this->product(10, 10);
        ConfiguracionNegocio::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => true,
            'porcentaje_impuesto' => 12.5,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '12.00',
            'clave_idempotencia' => 'tax-125',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('1.25', number_format($venta->impuesto, 2));
        $this->assertSame('11.25', number_format($venta->total, 2));
        $this->assertSame('12.50', number_format((float) $venta->porcentaje_impuesto, 2));
    }

    public function test_impuesto_se_aplica_sobre_el_subtotal_con_descuento(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);

        $category = Category::create(['nombre' => 'Pruebas']);
        $producto = Product::create([
            'categoria_id' => $category->id,
            'nombre' => 'Producto con descuento',
            'precio' => 10,
            'existencias' => 10,
            'descuento' => 10,
            'esta_activo' => true,
        ]);

        ConfiguracionNegocio::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => true,
            'porcentaje_impuesto' => 15,
            'descuento_activo' => true,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '11.00',
            'clave_idempotencia' => 'tax-descuento',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('1.00', number_format($venta->descuento, 2));
        $this->assertSame('1.35', number_format($venta->impuesto, 2));
        $this->assertSame('10.35', number_format($venta->total, 2));
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
