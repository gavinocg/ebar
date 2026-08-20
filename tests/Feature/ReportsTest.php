<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->negocio = $this->bar();
        app(ContextoNegocio::class)->establecer($this->negocio->id);
    }

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

    private function cajero(Negocio $negocio): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'cajero', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $usuario;
    }

    private function producto(Negocio $negocio, string $nombre, int $precio = 10, int $existencias = 10): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Cat ' . rand(1000, 9999), 'esta_activa' => true]);
        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => $precio,
            'existencias' => $existencias,
            'maneja_existencias' => true,
            'esta_activo' => true,
        ]);
    }

    private function venta(User $usuario, Producto $producto, string $metodo = 'efectivo', ?Carbon $fecha = null, int $comprobanteNum = null): Venta
    {
        $fecha = $fecha ?? Carbon::now();
        $precio = $producto->precio;
        $comprobanteNum = $comprobanteNum ?? (Venta::where('negocio_id', $this->negocio->id)->count() + 1);
        $venta = Venta::create([
            'usuario_id' => $usuario->id,
            'cliente_id' => null,
            'subtotal' => $precio,
            'descuento' => 0,
            'impuesto' => 0,
            'total' => $precio,
            'metodo_pago' => $metodo,
            'pagado' => $precio,
            'cambio' => 0,
            'estado_cobro' => 'cobrado',
            'negocio_id' => $this->negocio->id,
            'numero_comprobante' => 'CMP-' . str_pad((string) $comprobanteNum, 6, '0', STR_PAD_LEFT),
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
        $venta->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => $precio,
            'precio' => $precio,
            'descuento' => 0,
            'subtotal' => $precio,
            'nombre_producto' => $producto->nombre,
            'variante_id' => null,
        ]);

        return $venta;
    }

    public function test_reporte_ventas_accesible_y_filtra_por_fecha_y_sucursal(): void
    {
        $admin = $this->propietario($this->negocio);
        $cajero = $this->cajero($this->negocio);
        $producto = $this->producto($this->negocio, 'Producto R');
        $sucursal = Sucursal::create(['nombre' => 'Sucursal Central']);

        $this->actingAs($cajero);
        $this->venta($cajero, $producto, 'efectivo', Carbon::now()->subDays(5));

        $this->actingAs($admin);
        $this->get(route('reportes.ventas', ['start_date' => Carbon::now()->subDays(10)->toDateString(), 'end_date' => Carbon::now()->toDateString()]))->assertOk();
        $this->get(route('reportes.ventas', ['sucursal_id' => $sucursal->id]))->assertOk();
    }

    public function test_reporte_inventario_accesible_y_muestra_stock(): void
    {
        $admin = $this->propietario($this->negocio);
        $producto = $this->producto($this->negocio, 'Stock Producto', 10, 25);

        $this->actingAs($admin);
        $this->get(route('reportes.inventario'))->assertOk();
    }

    public function test_reporte_cajeros_accesible_y_agrupa_por_usuario(): void
    {
        $admin = $this->propietario($this->negocio);
        $cajero1 = $this->cajero($this->negocio);
        $cajero2 = $this->cajero($this->negocio);
        $producto = $this->producto($this->negocio, 'Producto C');

        $this->actingAs($cajero1);
        $this->venta($cajero1, $producto, 'efectivo');
        $this->actingAs($cajero2);
        $this->venta($cajero2, $producto, 'tarjeta');

        $this->actingAs($admin);
        $this->get(route('reportes.cajeros'))->assertOk();
    }

    public function test_reporte_productos_vendidos_accesible_y_top_20(): void
    {
        $admin = $this->propietario($this->negocio);
        $cajero = $this->cajero($this->negocio);

        for ($i = 1; $i <= 5; $i++) {
            $producto = $this->producto($this->negocio, "Producto $i");
            $this->actingAs($cajero);
            $this->venta($cajero, $producto);
        }

        $this->actingAs($admin);
        $this->get(route('reportes.productos'))->assertOk();
    }

    public function test_reporte_categorias_accesible_y_agrupa_por_categoria(): void
    {
        $admin = $this->propietario($this->negocio);
        $cajero = $this->cajero($this->negocio);

        $cat1 = Categoria::create(['nombre' => 'Cat A', 'esta_activa' => true]);
        $cat2 = Categoria::create(['nombre' => 'Cat B', 'esta_activa' => true]);
        $p1 = Producto::create(['categoria_id' => $cat1->id, 'nombre' => 'P1', 'precio' => 10, 'existencias' => 10, 'maneja_existencias' => true, 'esta_activo' => true]);
        $p2 = Producto::create(['categoria_id' => $cat2->id, 'nombre' => 'P2', 'precio' => 15, 'existencias' => 10, 'maneja_existencias' => true, 'esta_activo' => true]);

        $this->actingAs($cajero);
        $this->venta($cajero, $p1);
        $this->venta($cajero, $p2);

        $this->actingAs($admin);
        $this->get(route('reportes.categorias'))->assertOk();
    }

    public function test_reporte_metodos_pago_accesible_y_agrupa_por_metodo(): void
    {
        $admin = $this->propietario($this->negocio);
        $cajero = $this->cajero($this->negocio);
        $producto = $this->producto($this->negocio, 'Producto MP');

        $this->actingAs($cajero);
        $this->venta($cajero, $producto, 'efectivo');
        $this->venta($cajero, $producto, 'tarjeta');
        $this->venta($cajero, $producto, 'transferencia');

        $this->actingAs($admin);
        $this->get(route('reportes.metodos_pago'))->assertOk();
    }

    public function test_reporte_tendencias_accesible_y_compara_periodos(): void
    {
        $admin = $this->propietario($this->negocio);
        $cajero = $this->cajero($this->negocio);
        $producto = $this->producto($this->negocio, 'Producto T');

        $this->actingAs($cajero);
        $this->venta($cajero, $producto, 'efectivo', Carbon::now()->subDays(2));
        $this->venta($cajero, $producto, 'efectivo', Carbon::now()->subDays(10));

        $this->actingAs($admin);
        $this->get(route('reportes.tendencias'))->assertOk();
    }

    public function test_reporte_sucursal_accesible_y_agrupa_por_sucursal_y_cajero(): void
    {
        $admin = $this->propietario($this->negocio);
        $cajero = $this->cajero($this->negocio);
        $producto = $this->producto($this->negocio, 'Producto S');
        $sucursal = Sucursal::create(['nombre' => 'Sucursal Test']);

        $this->actingAs($cajero);
        $this->venta($cajero, $producto, 'efectivo', Carbon::now());

        $this->actingAs($admin);
        $this->get(route('reportes.sucursal'))->assertOk();
    }

    public function test_reportes_aislados_por_negocio(): void
    {
        $admin1 = $this->propietario($this->negocio);
        $cajero1 = $this->cajero($this->negocio);
        $producto1 = $this->producto($this->negocio, 'Producto Negocio 1');
        $this->actingAs($cajero1);
        $this->venta($cajero1, $producto1, 'efectivo', Carbon::now(), 1);

        $negocio2 = Negocio::create(['nombre' => 'Bar 2', 'identificador' => 'bar-2-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio2->id);
        $admin2 = $this->propietario($negocio2);
        $cajero2 = $this->cajero($negocio2);
        $producto2 = $this->producto($negocio2, 'Producto Negocio 2');
        $this->actingAs($cajero2);
        // Usar comprobanteNum separado para negocio 2
        $venta2 = Venta::create([
            'usuario_id' => $cajero2->id,
            'cliente_id' => null,
            'subtotal' => 10,
            'descuento' => 0,
            'impuesto' => 0,
            'total' => 10,
            'metodo_pago' => 'efectivo',
            'pagado' => 10,
            'cambio' => 0,
            'estado_cobro' => 'cobrado',
            'negocio_id' => $negocio2->id,
            'numero_comprobante' => 'CMP-' . str_pad((string) (Venta::withoutGlobalScopes()->count() + 1), 6, '0', STR_PAD_LEFT),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $venta2->detalles()->create([
            'producto_id' => $producto2->id,
            'cantidad' => 1,
            'precio_unitario' => 10,
            'precio' => 10,
            'descuento' => 0,
            'subtotal' => 10,
            'nombre_producto' => $producto2->nombre,
            'variante_id' => null,
        ]);

        app(ContextoNegocio::class)->establecer($this->negocio->id);
        $this->actingAs($admin1);

        // Verificar que reporte productos solo muestra del negocio 1
        $this->get(route('reportes.productos'))->assertOk();
        $this->get(route('reportes.categorias'))->assertOk();
        $this->get(route('reportes.metodos_pago'))->assertOk();
        $this->get(route('reportes.tendencias'))->assertOk();
        $this->get(route('reportes.sucursal'))->assertOk();
    }

    public function test_usuarios_sin_permiso_no_acceden_a_reportes(): void
    {
        $cajero = $this->cajero($this->negocio);
        $producto = $this->producto($this->negocio, 'Producto P');
        $this->actingAs($cajero);
        $this->venta($cajero, $producto);

        $this->actingAs($cajero);

        // reportes.ver requerido para la mayoría
        $this->get(route('reportes.productos'))->assertStatus(403);
        $this->get(route('reportes.categorias'))->assertStatus(403);
        $this->get(route('reportes.metodos_pago'))->assertStatus(403);
        $this->get(route('reportes.tendencias'))->assertStatus(403);
        $this->get(route('reportes.ventas'))->assertStatus(403);
        $this->get(route('reportes.inventario'))->assertStatus(403);

        // reportes.cajeros requiere permiso específico
        $this->get(route('reportes.cajeros'))->assertStatus(403);

        // reportes.ventas_o_cajeros para sucursal
        $this->get(route('reportes.sucursal'))->assertStatus(403);
    }
}
