<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteModelBindingTest extends TestCase
{
    use RefreshDatabase;

    private function negocioConPropietario(string $nombre): array
    {
        $negocio = Negocio::create([
            'nombre' => $nombre,
            'identificador' => str()->slug($nombre) . '-' . str()->random(6),
            'esta_activo' => true,
        ]);

        $usuario = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'propietario',
            'esta_activa' => true,
        ]);

        return compact('negocio', 'usuario');
    }

    public function test_no_se_puede_eliminar_una_categoria_de_otro_negocio(): void
    {
        $barA = $this->negocioConPropietario('Bar A');
        $barB = $this->negocioConPropietario('Bar B');

        app(ContextoNegocio::class)->establecer($barB['negocio']->id);
        $categoriaB = Categoria::create(['nombre' => 'Categoría de B']);

        $this->actingAs($barA['usuario']);
        app(ContextoNegocio::class)->establecer($barA['negocio']->id);

        $this->delete(route('categorias.destroy', $categoriaB))->assertNotFound();

        $this->assertDatabaseHas('categorias', ['id' => $categoriaB->id]);
    }

    public function test_no_se_puede_ver_una_venta_de_otro_negocio(): void
    {
        $barA = $this->negocioConPropietario('Bar A');
        $barB = $this->negocioConPropietario('Bar B');

        app(ContextoNegocio::class)->establecer($barB['negocio']->id);
        $ventaB = Venta::create([
            'negocio_id' => $barB['negocio']->id,
            'numero_comprobante' => 'CMP-B-' . str()->random(6),
            'subtotal' => 10,
            'total' => 10,
            'metodo_pago' => 'efectivo',
            'pagado' => 10,
        ]);

        $this->actingAs($barA['usuario']);
        app(ContextoNegocio::class)->establecer($barA['negocio']->id);

        $this->get(route('ventas.show', $ventaB))->assertNotFound();
    }
}
