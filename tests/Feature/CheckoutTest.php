<?php

namespace Tests\Feature;

use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Models\Categoria as Category;
use App\Models\Producto as Product;
use App\Models\Caja;
use App\Models\TurnoCaja;
use App\Models\Negocio;
use App\Services\ContextoNegocio;
use App\Models\Impresora;
use App\Models\Venta;
use App\Services\ServicioImpresoraTermica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_punto_de_venta_renderiza_su_interfaz(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('punto_venta.inicio'))->assertOk();
    }

    public function test_super_administrador_puede_ver_la_plataforma(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'super_admin']));

        $this->get(route('plataforma.inicio'))->assertOk();
    }

    public function test_usuario_del_bar_no_puede_ver_la_plataforma(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'cajero']));

        $this->get(route('plataforma.inicio'))->assertForbidden();
    }

    public function test_los_datos_se_aislan_por_negocio(): void
    {
        $negocioUno = Negocio::create([
            'nombre' => 'Negocio Uno',
            'identificador' => 'negocio-uno',
            'esta_activo' => true,
        ]);
        $negocioDos = Negocio::create([
            'nombre' => 'Negocio Dos',
            'identificador' => 'negocio-dos',
            'esta_activo' => true,
        ]);
        $contexto = app(ContextoNegocio::class);

        $contexto->establecer($negocioUno->id);
        Category::create(['nombre' => 'Categoría Uno']);
        $contexto->establecer($negocioDos->id);
        Category::create(['nombre' => 'Categoría Dos']);

        $contexto->establecer($negocioUno->id);
        $this->assertSame(['Categoría Uno'], Category::pluck('nombre')->all());
        $contexto->establecer($negocioDos->id);
        $this->assertSame(['Categoría Dos'], Category::pluck('nombre')->all());
    }

    public function test_paginas_principales_renderizan(): void
    {
        $this->actingAs($this->adminBar());
        $this->product();

        foreach ([
            'panel.inicio',
            'categorias.index',
            'productos.index',
            'ventas.index',
            'impresoras.index',
            'configuracion.negocio',
            'reportes.ventas',
            'reportes.inventario',
        ] as $nombreRuta) {
            $this->get(route($nombreRuta))->assertOk();
        }
    }

    public function test_checkout_rejects_invalid_quantities(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product();

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => -1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'invalid-quantity-test',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_producto_sin_control_de_existencias_se_vende_sin_limite(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $categoria = Category::create(['nombre' => 'Ilimitados']);
        $producto = Product::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto permanente',
            'precio' => 10,
            'existencias' => 0,
            'maneja_existencias' => false,
            'esta_activo' => true,
        ]);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 50]],
            'metodo_pago' => 'efectivo',
            'pagado' => '600.00',
            'clave_idempotencia' => 'producto-permanente-test',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'existencias' => 0]);
        $this->assertDatabaseMissing('movimientos_inventario', ['producto_id' => $producto->id]);
    }

    public function test_checkout_is_integral_and_idempotent(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $product = $this->product(price: 10, stock: 3);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $payload = [
            'items' => [['producto_id' => $product->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '25.00',
            'clave_idempotencia' => 'same-sale-test',
        ];

        $firstResponse = $this->postJson(route('punto_venta.cobrar'), $payload);
        $secondResponse = $this->postJson(route('punto_venta.cobrar'), $payload);

        $firstResponse->assertOk()->assertJsonPath('success', true);
        $secondResponse->assertOk()->assertJsonPath('sale.numero_comprobante', $firstResponse->json('sale.numero_comprobante'));
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $product->id,
            'tipo' => 'venta',
            'cantidad' => -2,
            'existencias_anteriores' => 3,
            'existencias_posteriores' => 1,
        ]);
        $this->assertDatabaseHas('productos', ['id' => $product->id, 'existencias' => 1]);
        $this->assertDatabaseHas('movimientos_efectivo', [
            'tipo' => 'venta',
            'monto' => 25,
            'turno_caja_id' => $this->turnoActual($usuario)->id,
        ]);
    }

    public function test_caja_se_puede_abrir_y_cerrar_con_arqueo(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        Caja::create(['nombre' => 'Caja de pruebas', 'esta_activa' => true]);

        $this->post(route('caja.abrir'), ['fondo_inicial' => '100.00'])
            ->assertRedirect();
        $this->assertDatabaseHas('turnos_caja', [
            'usuario_id' => $usuario->id,
            'estado' => 'abierta',
            'fondo_inicial' => 100,
        ]);

        $this->post(route('caja.movimiento'), [
            'tipo' => 'entrada',
            'monto' => '25.00',
            'motivo' => 'Cambio adicional',
        ])->assertRedirect();

        $this->post(route('caja.cerrar'), ['efectivo_contado' => '125.00'])
            ->assertRedirect();
        $this->assertDatabaseHas('turnos_caja', [
            'usuario_id' => $usuario->id,
            'estado' => 'cerrada',
            'efectivo_esperado' => 125,
            'diferencia' => 0,
        ]);
    }

    public function test_producto_y_categoria_aceptan_recursos_visuales(): void
    {
        Storage::fake('public');
        $this->actingAs($this->adminBar());

        $categoriaResponse = $this->post(route('categorias.store'), [
            'nombre' => 'Bebidas',
            'descripcion' => 'Bebidas frías',
            'color' => '#2563eb',
            'icono' => 'bi bi-cup-straw',
            'orden' => 1,
            'esta_activa' => 1,
            'imagen' => UploadedFile::fake()->image('bebidas.webp', 500, 500),
        ]);
        $categoriaResponse->assertRedirect();
        $categoria = Category::firstOrFail();

        $productoResponse = $this->post(route('productos.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Jugo natural',
            'precio' => 2.50,
            'existencias' => 20,
            'color' => '#fef3c7',
            'distintivo' => 'Nuevo',
            'distintivo_color' => '#16a34a',
            'destacado' => 1,
            'esta_activo' => 1,
            'imagen' => UploadedFile::fake()->image('jugo.png', 500, 500),
        ]);
        $productoResponse->assertRedirect();

        $producto = Product::firstOrFail();
        $this->assertNotEmpty($categoria->fresh()->imagen_path);
        $this->assertNotEmpty($producto->imagen_path);
        $this->assertDatabaseHas('productos', [
            'distintivo' => 'Nuevo',
            'destacado' => 1,
            'color' => '#fef3c7',
        ]);
    }

    public function test_comprobante_termico_de_58mm_usa_columnas_compactas(): void
    {
        $impresora = Impresora::create([
            'nombre' => 'Bluetooth 58mm',
            'tipo_conexion' => 'bluetooth',
            'tipo_impresora' => 'termica',
            'ancho_papel' => '58mm',
            'esta_activa' => true,
            'es_predeterminada' => true,
        ]);
        $venta = Venta::create([
            'numero_comprobante' => 'CMP-000001',
            'clave_idempotencia' => 'ticket-format-test',
            'subtotal' => 1.25,
            'impuesto' => 0,
            'impuesto_habilitado' => false,
            'porcentaje_impuesto' => 0,
            'total' => 1.25,
            'metodo_pago' => 'efectivo',
            'pagado' => 2,
            'cambio' => 0.75,
        ]);
        $venta->detalles()->create([
            'producto_id' => $this->product()->id,
            'nombre_producto' => 'Producto1',
            'cantidad' => 1,
            'precio' => 0.50,
            'subtotal' => 0.50,
        ]);
        $venta->detalles()->create([
            'producto_id' => $this->product()->id,
            'nombre_producto' => 'Producto2',
            'cantidad' => 1,
            'precio' => 0.75,
            'subtotal' => 0.75,
        ]);

        $comprobante = (new ServicioImpresoraTermica($impresora))->imprimirComprobante($venta->load('detalles'));

        $this->assertStringContainsString(str_repeat('-', 32), $comprobante);
        $this->assertStringContainsString('1xProducto1', $comprobante);
        $this->assertStringContainsString('Total', $comprobante);
        $this->assertStringContainsString('GRACIAS POR SU COMPRA!', $comprobante);
        $this->assertStringNotContainsString('¡', $comprobante);
    }

    public function test_checkout_aplica_descuento_por_producto_y_por_comprobante(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        
        $category = Category::create(['nombre' => 'Pruebas']);
        $productoConDescuento = Product::create([
            'categoria_id' => $category->id,
            'nombre' => 'Producto con descuento',
            'precio' => 100,
            'descuento' => 10, // 10%
            'existencias' => 10,
            'esta_activo' => true,
        ]);
        $productoSinDescuento = Product::create([
            'categoria_id' => $category->id,
            'nombre' => 'Producto normal',
            'precio' => 50,
            'descuento' => 0,
            'existencias' => 10,
            'esta_activo' => true,
        ]);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $payload = [
            'items' => [
                ['producto_id' => $productoConDescuento->id, 'cantidad' => 2], // 200 - 20 = 180
                ['producto_id' => $productoSinDescuento->id, 'cantidad' => 1], // 50
            ],
            'metodo_pago' => 'efectivo',
            'pagado' => '200.00',
            'clave_idempotencia' => 'descuento-test',
            'descuento' => 20, // 20% sobre subtotal (180+50=230 => 46)
        ];

        $response = $this->postJson(route('punto_venta.cobrar'), $payload);

        $response->assertOk()->assertJsonPath('success', true);
        $venta = Venta::first();
        
        // Subtotal antes de descuento comprobante: 180 + 50 = 230
        // Descuento líneas: 20 (10% de 200)
        // Descuento comprobante: 20% de 230 = 46
        // Total descuento: 20 + 46 = 66
        // Subtotal final (gravable): 230 - 46 = 184
        // Sin impuesto: total = 184
        $this->assertSame('184.00', number_format($venta->subtotal, 2));
        $this->assertSame('66.00', number_format($venta->descuento, 2));
        $this->assertSame('20.00', number_format($venta->descuento_porcentaje, 2));
        $this->assertSame('184.00', number_format($venta->total, 2));
        
        // Detalles congelan descuentos
        $detalles = $venta->detalles()->orderBy('id')->get();
        $this->assertSame('20.00', number_format($detalles[0]->descuento, 2)); // 10% de 2*100
        $this->assertSame('0.00', number_format($detalles[1]->descuento, 2));
        $this->assertSame('180.00', number_format($detalles[0]->subtotal, 2)); // 200 - 20
        $this->assertSame('50.00', number_format($detalles[1]->subtotal, 2));
    }

    public function test_reembolso_total_revierte_existencias_y_registra_efectivo(): void
    {
        $usuario = $this->adminBar();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $product = $this->product(price: 10, stock: 3);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '20.00',
            'clave_idempotencia' => 'venta-para-reembolso',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame(1, (int) $product->fresh()->existencias);

        $response = $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Cliente devolvió el pedido',
            'metodo' => 'efectivo',
            'items' => [$venta->detalles->first()->id => 2],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reembolsos', [
            'venta_id' => $venta->id,
            'tipo' => 'total',
            'monto' => 20,
        ]);
        $this->assertSame(3, (int) $product->fresh()->existencias);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $product->id,
            'tipo' => 'devolucion',
            'cantidad' => 2,
        ]);
        $this->assertDatabaseHas('movimientos_efectivo', [
            'tipo' => 'retiro',
            'monto' => 20,
            'motivo' => 'Reembolso ' . $venta->numero_comprobante,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'ventas',
            'accion' => 'reembolso',
        ]);
    }

    public function test_reembolso_parcial_respeta_cantidad_disponible(): void
    {
        $usuario = $this->adminBar();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $product = $this->product(price: 10, stock: 5);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 3]],
            'metodo_pago' => 'efectivo',
            'pagado' => '30.00',
            'clave_idempotencia' => 'venta-reembolso-parcial',
        ])->assertOk();

        $venta = Venta::first();
        $detalle = $venta->detalles->first();

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'parcial',
            'motivo' => 'Devolución de una unidad',
            'metodo' => 'efectivo',
            'items' => [$detalle->id => 1],
        ])->assertRedirect();

        $this->assertSame(3, (int) $product->fresh()->existencias); // 2 + 1 devuelta

        $excesivo = $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'parcial',
            'motivo' => 'Intenta devolver más de lo disponible',
            'metodo' => 'efectivo',
            'items' => [$detalle->id => 3], // quedan 2 disponibles
        ]);
        $excesivo->assertRedirect();
        $this->assertDatabaseCount('reembolsos', 1); // el segundo no se creó
        $this->assertSame(3, (int) $product->fresh()->existencias);
    }

    public function test_reembolso_requiere_admin_del_bar(): void
    {
        $usuario = User::factory()->create(['rol' => 'cajero']);
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $product = $this->product(price: 10, stock: 2);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'venta-cajero-reembolso',
        ])->assertOk();

        $venta = Venta::first();

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Sin autorización',
            'metodo' => 'efectivo',
            'items' => [$venta->detalles->first()->id => 1],
        ])->assertForbidden();

        $this->assertDatabaseCount('reembolsos', 0);
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

    private function turnoActual(User $usuario): TurnoCaja
    {
        return TurnoCaja::where('usuario_id', $usuario->id)->where('estado', 'abierta')->latest('id')->firstOrFail();
    }

    private function adminBar(): User
    {
        $negocio = Negocio::firstOrCreate(
            ['identificador' => 'negocio-principal'],
            ['nombre' => 'Negocio principal', 'esta_activo' => true],
        );
        app(ContextoNegocio::class)->establecer($negocio->id);

        $usuario = User::factory()->create(['rol' => 'admin_bar']);
        \App\Models\MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'admin_bar',
            'esta_activa' => true,
        ]);

        return $usuario;
    }
}
