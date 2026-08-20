<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ConteoInventario;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtomicidadTest extends TestCase
{
    use RefreshDatabase;

    private function bar(): Negocio
    {
        $negocio = Negocio::create([
            'nombre' => 'Bar Atómico',
            'identificador' => 'bar-atomico-' . str()->random(6),
            'esta_activo' => true,
        ]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $negocio;
    }

    private function propietario(Negocio $negocio): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'propietario',
            'esta_activa' => true,
        ]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $usuario;
    }

    private function producto(Negocio $negocio, string $nombre): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Comida']);
        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => 5,
            'existencias' => 10,
            'esta_activo' => true,
        ]);
    }

    public function test_las_ordenes_de_compra_generan_numeros_unicos(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Cerveza');
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora']);

        $this->actingAs($admin);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('ordenes.store'), [
                'proveedor_id' => $proveedor->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 1]],
            ])->assertRedirect();
        }

        $numeros = OrdenCompra::withoutGlobalScopes()->pluck('numero')->all();
        $this->assertCount(3, $numeros);
        $this->assertCount(3, array_unique($numeros), 'Los números de orden deben ser únicos.');
    }

    public function test_los_conteos_generan_numeros_unicos(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Refresco');

        $this->actingAs($admin);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('conteos.store'), [
                'productos' => [['producto_id' => $producto->id, 'existencias_reales' => 10]],
            ])->assertRedirect();
        }

        $numeros = ConteoInventario::withoutGlobalScopes()->pluck('numero')->all();
        $this->assertCount(3, $numeros);
        $this->assertCount(3, array_unique($numeros), 'Los números de conteo deben ser únicos.');
    }

    public function test_un_numero_de_orden_duplicado_viola_la_restriccion_unica(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);

        OrdenCompra::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $admin->id,
            'numero' => 'DUP-001',
            'subtotal' => 0,
            'total' => 0,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        OrdenCompra::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $admin->id,
            'numero' => 'DUP-001',
            'subtotal' => 0,
            'total' => 0,
        ]);
    }
}
