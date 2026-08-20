<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\User;
use App\Models\MembresiaNegocio;
use App\Models\TurnoCajero;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\GrupoModificador;
use App\Models\Modificador;
use App\Models\ConfiguracionNegocio;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function setupBusiness(): array
    {
        $negocio = Negocio::create(['nombre' => 'Bar A', 'identificador' => 'bar-a-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $admin = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $admin->id, 'rol' => 'propietario', 'esta_activa' => true]);

        $categoria = Categoria::create(['nombre' => 'Bebidas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Cerveza A',
            'precio' => 3.00,
            'existencias' => 50,
            'esta_activo' => true,
        ]);

        ConfiguracionNegocio::create(['nombre_negocio' => 'Bar A', 'cobrar_impuesto' => false, 'porcentaje_impuesto' => 0]);

        return compact('negocio', 'admin', 'categoria', 'producto');
    }

    private function setupSecondBusiness(): array
    {
        $negocio = Negocio::create(['nombre' => 'Bar B', 'identificador' => 'bar-b-' . str()->random(6), 'esta_activo' => true]);
        $admin = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $admin->id, 'rol' => 'propietario', 'esta_activa' => true]);

        $savedContext = app(ContextoNegocio::class)->id();
        app(ContextoNegocio::class)->establecer($negocio->id);

        $categoria = Categoria::create(['nombre' => 'Pizzas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Pizza B',
            'precio' => 8.00,
            'existencias' => 20,
            'esta_activo' => true,
        ]);

        ConfiguracionNegocio::create(['nombre_negocio' => 'Bar B', 'cobrar_impuesto' => false, 'porcentaje_impuesto' => 0]);

        app(ContextoNegocio::class)->establecer($savedContext);

        return compact('negocio', 'admin', 'categoria', 'producto');
    }

    public function test_usuario_no_puede_ver_datos_de_otro_negocio(): void
    {
        $barA = $this->setupBusiness();
        $barB = $this->setupSecondBusiness();

        $this->actingAs($barA['admin']);
        app(ContextoNegocio::class)->establecer($barA['negocio']->id);

        $productos = \App\Models\Producto::all();
        $this->assertCount(1, $productos);
        $this->assertEquals('Cerveza A', $productos->first()->nombre);

        $categorias = \App\Models\Categoria::all();
        $this->assertCount(1, $categorias);
        $this->assertEquals('Bebidas', $categorias->first()->nombre);
    }

    public function test_configuracion_es_por_negocio(): void
    {
        $barA = $this->setupBusiness();
        $barB = $this->setupSecondBusiness();

        $this->actingAs($barA['admin']);
        app(ContextoNegocio::class)->establecer($barA['negocio']->id);

        $configA = ConfiguracionNegocio::first();
        $this->assertEquals('Bar A', $configA->nombre_negocio);

        app(ContextoNegocio::class)->establecer($barB['negocio']->id);

        $configB = ConfiguracionNegocio::first();
        $this->assertEquals('Bar B', $configB->nombre_negocio);
    }

    public function test_venta_pertenece_al_negocio_correcto(): void
    {
        $barA = $this->setupBusiness();

        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $barA['negocio']->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $turno = TurnoCajero::create([
            'usuario_id' => $cajero->id,
            'negocio_id' => $barA['negocio']->id,
            'sucursal_id' => null,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        $this->actingAs($cajero);
        app(ContextoNegocio::class)->establecer($barA['negocio']->id);
        session(['pos_desbloqueado' => true, 'turno_cajero_id' => $turno->id]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $barA['producto']->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '5.00',
            'clave_idempotencia' => 'test-tenant-' . str()->random(10),
        ]);

        $response->assertOk();

        $venta = \App\Models\Venta::first();
        $this->assertEquals($barA['negocio']->id, $venta->negocio_id);
    }

    public function test_cajero_no_puede_acceder_a_reportes(): void
    {
        $barA = $this->setupBusiness();

        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $barA['negocio']->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $this->actingAs($cajero);
        app(ContextoNegocio::class)->establecer($barA['negocio']->id);

        $this->get(route('reportes.productos'))->assertForbidden();
    }

    public function test_admin_no_puede_acceder_a_configuracion(): void
    {
        $barA = $this->setupBusiness();

        $adminBar = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $barA['negocio']->id, 'usuario_id' => $adminBar->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($adminBar);
        app(ContextoNegocio::class)->establecer($barA['negocio']->id);

        $this->get(route('configuracion.negocio'))->assertForbidden();
    }
}
