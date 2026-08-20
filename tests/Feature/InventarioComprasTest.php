<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ConteoInventario;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventarioComprasTest extends TestCase
{
    use RefreshDatabase;

    private function bar(): Negocio
    {
        $negocio = Negocio::create(['nombre' => 'Bar R', 'identificador' => 'bar-r-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);


        return $negocio;
    }

    private function propietario(Negocio $negocio): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'propietario', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $usuario;
    }

    private function categoria(Negocio $negocio): Categoria
    {
        return Categoria::create(['nombre' => 'Comida R', 'esta_activa' => true]);
    }

    private function producto(Negocio $negocio, string $nombre, int $existencias = 10, ?string $codigoBarras = null): Producto
    {
        return Producto::create([
            'categoria_id' => $this->categoria($negocio)->id,
            'nombre' => $nombre,
            'precio' => 5,
            'existencias' => $existencias,
            'maneja_existencias' => true,
            'codigo_barras' => $codigoBarras,
            'esta_activo' => true,
        ]);
    }

    public function test_una_orden_de_compra_no_se_puede_recibir_dos_veces(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Cerveza');
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora R']);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio_unitario' => 2]],
        ])->assertRedirect();

        $orden = OrdenCompra::firstOrFail();
        $this->post(route('ordenes.recibir', $orden))->assertRedirect();
        $this->assertSame(15.0, (float) $producto->fresh()->existencias);

        $this->post(route('ordenes.recibir', $orden))->assertStatus(422);
        $this->assertSame(15.0, (float) $producto->fresh()->existencias);
        $this->assertSame(1, OrdenCompra::where('estado', 'recibida')->count());
    }

    public function test_un_conteo_no_se_puede_aplicar_dos_veces(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Refresco', 10);

        $this->actingAs($admin);
        $this->post(route('conteos.store'), [
            'productos' => [['producto_id' => $producto->id, 'existencias_reales' => 12]],
        ])->assertRedirect();

        $conteo = ConteoInventario::firstOrFail();
        $this->post(route('conteos.aplicar', $conteo))->assertRedirect();
        $this->assertSame(12.0, (float) $producto->fresh()->existencias);

        $this->post(route('conteos.aplicar', $conteo))->assertStatus(422);
        $this->assertSame(12.0, (float) $producto->fresh()->existencias);
    }

    public function test_una_orden_no_acepta_productos_de_otro_bar(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);

        $otroBar = Negocio::create(['nombre' => 'Bar Ajeno', 'identificador' => 'bar-ajeno-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($otroBar->id);
        $productoAjeno = $this->producto($otroBar, 'Producto ajeno');

        app(ContextoNegocio::class)->establecer($negocio->id);
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora R']);

        $this->actingAs($admin);

        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $productoAjeno->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ])->assertSessionHasErrors('items.0.producto_id');

        $this->assertDatabaseCount('ordenes_compra', 0);
    }

    public function test_el_codigo_de_barras_puede_repetirse_entre_bares_pero_no_dentro_del_mismo(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $this->producto($negocio, 'A', 5, 'BAR-001');

        $anotherBar = Negocio::create(['nombre' => 'Bar Dos', 'identificador' => 'bar-dos-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($anotherBar->id);
        $this->producto($anotherBar, 'B', 5, 'BAR-001');

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($admin);

        $this->post(route('productos.store'), [
            'categoria_id' => Categoria::first()->id,
            'nombre' => 'Duplicado',
            'precio' => 1,
            'codigo_barras' => 'BAR-001',
        ])->assertSessionHasErrors('codigo_barras');
    }

    public function test_el_codigo_de_barras_puede_reusarse_tras_eliminar_el_producto(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'A', 5, 'BAR-002');

        $this->actingAs($admin);
        $this->delete(route('productos.destroy', $producto))->assertRedirect();

        $this->assertNull($producto->fresh()->codigo_barras);

        $this->post(route('productos.store'), [
            'categoria_id' => Categoria::first()->id,
            'nombre' => 'Reusado',
            'precio' => 1,
            'codigo_barras' => 'BAR-002',
        ])->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', ['nombre' => 'Reusado', 'codigo_barras' => 'BAR-002']);
    }

    public function test_un_producto_con_movimientos_de_inventario_no_se_puede_eliminar_y_se_pregunta_antes_de_desactivar(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Con historial');

        DB::table('movimientos_inventario')->insert([
            'negocio_id' => $negocio->id,
            'sucursal_id' => null,
            'producto_id' => $producto->id,
            'tipo' => 'ajuste',
            'cantidad' => 1,
            'existencias_anteriores' => 10,
            'existencias_posteriores' => 11,
            'notas' => 'prueba',
            'usuario_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);
        $this->delete(route('productos.destroy', $producto))->assertSessionHas('no_eliminable');

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'esta_activo' => true]);

        $this->post(route('productos.desactivar', $producto))->assertRedirect(route('productos.index'));
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'esta_activo' => false]);
    }

    public function test_un_proveedor_con_ordenes_no_se_puede_eliminar_y_se_pregunta_antes_de_desactivar(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Cerveza');
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora R']);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio_unitario' => 2]],
        ])->assertRedirect();

        $this->delete(route('proveedores.destroy', $proveedor))->assertSessionHas('no_eliminable');

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'esta_activo' => true]);

        $this->post(route('proveedores.desactivar', $proveedor))->assertRedirect(route('proveedores.index'));
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'esta_activo' => false]);
    }

    public function test_desactivar_el_control_de_existencias_conserva_el_stock(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Snacks', 10);

        $this->actingAs($admin);
        $this->put(route('productos.update', $producto), [
            'categoria_id' => $producto->categoria_id,
            'nombre' => 'Snacks',
            'precio' => 5,
            'maneja_existencias' => 0,
            'existencias' => 999,
        ])->assertRedirect(route('productos.index'));

        $producto->refresh();
        $this->assertFalse($producto->maneja_existencias);
        $this->assertSame(10.0, (float) $producto->existencias);
    }

    public function test_importar_valida_cada_fila_y_omite_las_invalidas(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $categoria = $this->categoria($negocio);

        $csv = "nombre,categoria,precio,descuento,existencias,nivel_minimo,codigo_barras,sucursal,maneja_existencias\n"
            . "Precio Negativo,{$categoria->nombre},-5,150,0,0,,,1\n"
            . "Categoria Fantasma,Ausente,10,0,5,0,,,1\n"
            . "Valida,{$categoria->nombre},10,0,5,0,,,1\n";

        $this->actingAs($admin);

        $this->post(route('productos.importar'), [
            'archivo' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])->assertRedirect();

        $this->assertDatabaseMissing('productos', ['nombre' => 'Categoria Fantasma']);
        $this->assertDatabaseHas('productos', ['nombre' => 'Precio Negativo', 'precio' => 0, 'descuento' => 100]);
        $this->assertDatabaseHas('productos', ['nombre' => 'Valida', 'precio' => 10]);
    }

    public function test_no_se_elimina_una_categoria_con_productos_y_se_pregunta_antes_de_desactivar(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Con productos');

        $this->actingAs($admin);

        $this->delete(route('categorias.destroy', $producto->categoria))->assertSessionHas('no_eliminable');
        $this->assertDatabaseHas('categorias', ['id' => $producto->categoria_id, 'esta_activa' => true]);

        $this->post(route('categorias.desactivar', $producto->categoria))->assertRedirect(route('categorias.index'));
        $this->assertDatabaseHas('categorias', ['id' => $producto->categoria_id, 'esta_activa' => false]);
    }

    public function test_la_numeracion_de_ordenes_es_global_y_consecutiva(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Producto R');
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora R']);

        $this->actingAs($admin);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ])->assertRedirect();

        $otroBar = Negocio::create(['nombre' => 'Bar Tres', 'identificador' => 'bar-tres-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($otroBar->id);
        $adminOtro = $this->propietario($otroBar);
        $productoOtro = $this->producto($otroBar, 'Producto Tres');
        $proveedorOtro = Proveedor::create(['nombre' => 'Distribuidora Tres']);

        $this->actingAs($adminOtro);
        $this->post(route('ordenes.store'), [
            'proveedor_id' => $proveedorOtro->id,
            'items' => [['producto_id' => $productoOtro->id, 'cantidad' => 1, 'precio_unitario' => 1]],
        ])->assertRedirect();

        $this->assertSame(['OC-00001', 'OC-00002'], OrdenCompra::withoutGlobalScopes()->orderBy('id')->pluck('numero')->all());
    }

    public function test_no_se_ajusta_el_stock_de_un_producto_controlado_por_variantes(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $producto = $this->producto($negocio, 'Con variantes', 10);
        ProductoVariante::create([
            'producto_id' => $producto->id,
            'nombre' => 'Grande',
            'precio' => 6,
            'stock' => 8,
            'esta_activo' => true,
        ]);

        $this->actingAs($admin);
        $respuesta = $this->post(route('inventario.ajustar'), [
            'producto_id' => $producto->id,
            'cantidad' => 5,
            'tipo' => 'entrada',
            'motivo' => 'Prueba',
        ]);
        $respuesta->assertSessionHasErrors('producto_id');

        $this->assertSame(10.0, (float) $producto->fresh()->existencias);
    }
}
