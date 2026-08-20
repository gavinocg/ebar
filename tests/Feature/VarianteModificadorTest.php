<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\ProductoVariante;
use App\Models\GrupoModificador;
use App\Models\Modificador;
use App\Models\Negocio;
use App\Models\TurnoCajero;
use App\Models\Venta;
use App\Models\ConfiguracionNegocio;
use App\Models\User;
use App\Models\MembresiaNegocio;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VarianteModificadorTest extends TestCase
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

    public function test_venta_con_variante_usa_precio_de_variante(): void
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
            'esta_activo' => true,
        ]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'variante_id' => $variante->id]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'test-variante-price',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $venta = Venta::first();
        $this->assertSame('7.00', number_format($venta->subtotal, 2));

        $detalle = $venta->detalles->first();
        $this->assertSame($variante->id, $detalle->producto_variante_id);
        $this->assertSame('3.50', number_format($detalle->precio, 2));
    }

    public function test_venta_con_modificadores_suma_precio_extra(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);

        $categoria = Categoria::create(['nombre' => 'Pizzas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Pizza Personal',
            'precio' => 8.00,
            'existencias' => 999,
            'esta_activo' => true,
            'maneja_existencias' => false,
        ]);

        $grupo = GrupoModificador::create([
            'negocio_id' => $tenant['negocio']->id,
            'nombre' => 'Extras',
            'requerido' => false,
            'min_seleccion' => 0,
            'max_seleccion' => 3,
            'esta_activo' => true,
        ]);

        $grupo->productos()->attach($producto->id);

        $mod1 = Modificador::create(['negocio_id' => $tenant['negocio']->id, 'grupo_modificador_id' => $grupo->id, 'nombre' => 'Extra queso', 'precio_extra' => 1.50, 'esta_activo' => true]);
        $mod2 = Modificador::create(['negocio_id' => $tenant['negocio']->id, 'grupo_modificador_id' => $grupo->id, 'nombre' => 'Jalapeños', 'precio_extra' => 0.75, 'esta_activo' => true]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [[
                'producto_id' => $producto->id,
                'cantidad' => 1,
                'modificadores' => [
                    ['modificador_id' => $mod1->id, 'precio_extra' => 1.50],
                    ['modificador_id' => $mod2->id, 'precio_extra' => 0.75],
                ],
            ]],
            'metodo_pago' => 'efectivo',
            'pagado' => '12.00',
            'clave_idempotencia' => 'test-modifiers',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $venta = Venta::first();
        $this->assertSame('10.25', number_format($venta->subtotal, 2));

        $detalle = $venta->detalles->first();
        $this->assertSame('10.25', number_format($detalle->precio, 2));
        $this->assertCount(2, $detalle->modificadores);
    }

    public function test_tickets_abiertos_guardan_variante_y_modificadores(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $tenant['turno']->id]);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Hamburguesa',
            'precio' => 6.00,
            'existencias' => 20,
            'esta_activo' => true,
        ]);

        $variante = ProductoVariante::create([
            'negocio_id' => $tenant['negocio']->id,
            'producto_id' => $producto->id,
            'nombre' => 'Doble carne',
            'precio' => 8.00,
            'esta_activo' => true,
        ]);

        $response = $this->postJson(route('tickets_abiertos.store'), [
            'nombre' => 'Ticket VIP',
            'items' => [[
                'producto_id' => $producto->id,
                'producto_variante_id' => $variante->id,
                'nombre_producto' => 'Hamburguesa - Doble carne',
                'cantidad' => 2,
                'precio' => 8.00,
                'descuento' => 0,
                'modificadores' => [['modificador_id' => 999, 'precio_extra' => 1.00]],
            ]],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $detalle = \App\Models\TicketAbiertoDetalle::first();
        $this->assertSame($variante->id, $detalle->producto_variante_id);
        $this->assertNotNull($detalle->modificadores);
        $this->assertCount(1, $detalle->modificadores);
    }

    public function test_pos_carga_variantes_y_grupos_modificadores(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);
        session(['pos_desbloqueado' => true]);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Café',
            'precio' => 2.00,
            'existencias' => 50,
            'esta_activo' => true,
        ]);

        ProductoVariante::create([
            'negocio_id' => $tenant['negocio']->id,
            'producto_id' => $producto->id,
            'nombre' => 'Doble shot',
            'precio' => 3.00,
            'esta_activo' => true,
        ]);

        $grupo = GrupoModificador::create([
            'negocio_id' => $tenant['negocio']->id,
            'nombre' => 'Leche',
            'requerido' => true,
            'min_seleccion' => 1,
            'max_seleccion' => 1,
            'esta_activo' => true,
        ]);
        $grupo->productos()->attach($producto->id);
        Modificador::create(['negocio_id' => $tenant['negocio']->id, 'grupo_modificador_id' => $grupo->id, 'nombre' => 'Entera', 'precio_extra' => 0, 'esta_activo' => true]);
        Modificador::create(['negocio_id' => $tenant['negocio']->id, 'grupo_modificador_id' => $grupo->id, 'nombre' => 'Avena', 'precio_extra' => 0.50, 'esta_activo' => true]);

        $response = $this->get(route('punto_venta.inicio'));
        $response->assertOk();
        $response->assertSee('data-variantes');
        $response->assertSee('data-grupos');
    }
}
