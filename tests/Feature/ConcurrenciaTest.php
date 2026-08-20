<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ConfiguracionNegocio;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Reembolso;
use App\Models\TurnoCajero;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrenciaTest extends TestCase
{
    use RefreshDatabase;

    private function setupTenant(): array
    {
        $negocio = Negocio::create([
            'nombre' => 'Bar Concurrencia',
            'identificador' => 'bar-conc-' . str()->random(6),
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
            'nombre_negocio' => 'Bar Concurrencia',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        return compact('negocio', 'cajero', 'admin', 'turno');
    }

    public function test_la_suma_de_reembolsos_parciales_no_excede_lo_pagado(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Cerveza',
            'precio' => 10,
            'existencias' => 10,
            'esta_activo' => true,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
            'metodo_pago' => 'efectivo',
            'pagado' => '30.00',
            'clave_idempotencia' => 'concurrencia-' . str()->random(8),
        ])->assertOk();

        $venta = Venta::first();
        $detalle = $venta->detalles->first();

        $this->actingAs($tenant['admin']);
        app(ContextoNegocio::class)->establecer($tenant['negocio']->id);

        foreach ([1, 1, 1] as $unidades) {
            $this->post(route('reembolsos.crear', $venta), [
                'tipo' => 'parcial',
                'motivo' => 'Devolución parcial',
                'metodo' => 'credito',
                'items' => [$detalle->id => $unidades],
            ])->assertRedirect();
        }

        $this->assertSame(3, Reembolso::where('venta_id', $venta->id)->count());
        $this->assertEqualsWithDelta(
            30.0,
            (float) Reembolso::where('venta_id', $venta->id)->sum('monto'),
            0.01,
            'La suma de reembolsos debe igualar el total pagado.'
        );

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'parcial',
            'motivo' => 'Excede lo pagado',
            'metodo' => 'credito',
            'items' => [$detalle->id => 1],
        ])->assertRedirect();

        $this->assertSame(3, Reembolso::where('venta_id', $venta->id)->count(), 'No debe permitirse exceder lo pagado.');
    }

    public function test_no_se_pueden_registrar_dos_reembolsos_totales(): void
    {
        $tenant = $this->setupTenant();
        $this->actingAs($tenant['cajero']);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Cerveza',
            'precio' => 10,
            'existencias' => 5,
            'esta_activo' => true,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '20.00',
            'clave_idempotencia' => 'concurrencia-total-' . str()->random(8),
        ])->assertOk();

        $venta = Venta::first();
        $detalle = $venta->detalles->first();

        $this->actingAs($tenant['admin']);
        app(ContextoNegocio::class)->establecer($tenant['negocio']->id);

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Devolución total',
            'metodo' => 'credito',
            'items' => [$detalle->id => 2],
        ])->assertRedirect();

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Segundo total',
            'metodo' => 'credito',
            'items' => [$detalle->id => 2],
        ])->assertRedirect();

        $this->assertSame(1, Reembolso::where('venta_id', $venta->id)->where('tipo', 'total')->count());
    }
}
