<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria as Category;
use App\Models\ConfiguracionNegocio;
use App\Models\Negocio;
use App\Models\Producto as Product;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exportar_productos_genera_csv_con_cabecera_y_datos(): void
    {
        $this->actingAs($this->adminBar());
        $this->configuracion();
        $producto = $this->product();

        $response = $this->get(route('productos.exportar'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('nombre,categoria,precio,existencias,nivel_minimo,codigo_barras,sucursal,maneja_existencias', $csv);
        $this->assertStringContainsString($producto->nombre, $csv);
    }

    public function test_exportar_productos_solo_incluye_productos_del_negocio(): void
    {
        $this->actingAs($this->adminBar());
        $this->configuracion();
        $this->product();

        $otroNegocio = Negocio::create([
            'nombre' => 'Otro negocio',
            'identificador' => 'otro-negocio',
            'esta_activo' => true,
        ]);
        $contextoGuardado = app(ContextoNegocio::class)->id();
        app(ContextoNegocio::class)->establecer($otroNegocio->id);
        Category::create(['nombre' => 'Otra categoría']);
        Product::create([
            'categoria_id' => Category::first()->id,
            'nombre' => 'Producto del otro bar',
            'precio' => 1,
            'existencias' => 1,
            'esta_activo' => true,
        ]);
        app(ContextoNegocio::class)->establecer($contextoGuardado);

        $csv = $this->get(route('productos.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Producto de prueba', $csv);
        $this->assertStringNotContainsString('Producto del otro bar', $csv);
    }

    public function test_exportar_ventas_genera_csv_con_cabecera_y_ventas(): void
    {
        $this->actingAs($this->adminBar());
        $this->configuracion();
        $usuario = $this->cajero();
        $this->abrirTurno($usuario);
        $producto = $this->product();

        $this->actingAs($usuario);
        $venta = $this->vender($producto);

        $this->actingAs($this->adminBar());
        $response = $this->get(route('reportes.exportar_ventas'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Fecha,Comprobante', $csv);
        $this->assertStringContainsString('Metodo Pago', $csv);
        $this->assertStringContainsString($venta->numero_comprobante, $csv);
        $this->assertStringContainsString('10.00', $csv);
    }

    public function test_exportar_ventas_filtra_por_rango_de_fechas(): void
    {
        $this->actingAs($this->adminBar());
        $this->configuracion();
        $usuario = $this->cajero();
        $this->abrirTurno($usuario);
        $producto = $this->product();

        $this->actingAs($usuario);
        $ventaHoy = $this->vender($producto);
        $ventaAyer = $this->vender($producto);
        $ventaAyer->timestamps = false;
        $ventaAyer->created_at = now()->subDay();
        $ventaAyer->save();

        $this->actingAs($this->adminBar());
        $csv = $this->get(route('reportes.exportar_ventas', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]))->assertOk()->streamedContent();

        $this->assertStringContainsString($ventaHoy->numero_comprobante, $csv);
        $this->assertStringNotContainsString($ventaAyer->numero_comprobante, $csv);
    }

    private function vender(Product $producto): Venta
    {
        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'csv-' . str()->random(8),
        ])->assertOk();

        return Venta::latest('id')->first();
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

    private function adminBar(): User
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
            'rol' => 'propietario',
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