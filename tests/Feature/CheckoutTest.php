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
use App\Models\Cliente;
use App\Models\ProductoVariante;
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
        $this->actingAs($this->cajero());

        $this->get(route('punto_venta.inicio'))->assertOk();
    }

    public function test_super_administrador_puede_ver_la_plataforma(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'super_admin']));

        $this->get(route('plataforma.inicio'))->assertOk();
    }

    public function test_usuario_del_bar_no_puede_ver_la_plataforma(): void
    {
        $this->actingAs(User::factory()->create());

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
        $this->actingAs($this->cajero());
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
        $usuario = $this->cajero();
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
        $usuario = $this->cajero();
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
        $usuario = $this->cajero();
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

        $this->post(route('caja.cerrar'), [
            'es_final' => '1',
            'billetes' => [100 => 1, 20 => 1, 5 => 1, 50 => 0, 10 => 0, 1 => 0],
            'monedas' => [1 => 0, 0.50 => 0, 0.25 => 0, 0.10 => 0, 0.05 => 0, 0.01 => 0],
        ])->assertRedirect();
        $this->assertDatabaseHas('turnos_caja', [
            'usuario_id' => $usuario->id,
            'estado' => 'pendiente_aprobacion',
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

    public function test_checkout_no_aplica_descuento_aunque_producto_lo_tenga(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        
        $category = Category::create(['nombre' => 'Pruebas']);
        $productoConDescuento = Product::create([
            'categoria_id' => $category->id,
            'nombre' => 'Producto con descuento',
            'precio' => 100,
            'descuento' => 10,
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
                ['producto_id' => $productoConDescuento->id, 'cantidad' => 2],
                ['producto_id' => $productoSinDescuento->id, 'cantidad' => 1],
            ],
            'metodo_pago' => 'efectivo',
            'pagado' => '250.00',
            'clave_idempotencia' => 'sin-descuento-test',
        ];

        $response = $this->postJson(route('punto_venta.cobrar'), $payload);

        $response->assertOk()->assertJsonPath('success', true);
        $venta = Venta::first();
        
        // Sin descuentos: subtotal = 200 + 50 = 250
        $this->assertSame('250.00', number_format($venta->subtotal, 2));
        $this->assertSame('0.00', number_format($venta->descuento, 2));
        $this->assertSame('250.00', number_format($venta->total, 2));
        
        // Detalles sin descuento
        $detalles = $venta->detalles()->orderBy('id')->get();
        $this->assertSame('0.00', number_format($detalles[0]->descuento, 2));
        $this->assertSame('200.00', number_format($detalles[0]->subtotal, 2));
        $this->assertSame('50.00', number_format($detalles[1]->subtotal, 2));
    }

    public function test_reembolso_total_revierte_existencias_y_registra_efectivo(): void
    {
        $cajero = $this->cajero();
        $this->actingAs($cajero);
        $this->abrirTurno($cajero);
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

        $admin = $this->adminBar();
        $this->actingAs($admin);

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
            'monto' => -20,
            'motivo' => 'Reembolso ' . $venta->numero_comprobante,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'modulo' => 'ventas',
            'accion' => 'reembolso',
        ]);
    }

    public function test_reembolso_parcial_respeta_cantidad_disponible(): void
    {
        $cajero = $this->cajero();
        $this->actingAs($cajero);
        $this->abrirTurno($cajero);
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

        $admin = $this->adminBar();
        $this->actingAs($admin);

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

    public function test_cajero_puede_crear_un_reembolso(): void
    {
        $cajero = $this->cajero();
        $this->actingAs($cajero);
        $this->abrirTurno($cajero);
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
            'motivo' => 'Devolución autorizada',
            'metodo' => 'efectivo',
            'items' => [$venta->detalles->first()->id => 1],
        ])->assertRedirect();

        $this->assertDatabaseCount('reembolsos', 1);
        $this->assertSame(2, (int) $product->fresh()->existencias);
    }

    public function test_reembolso_en_efectivo_requiere_turno_abierto(): void
    {
        $cajero = $this->cajero();
        $this->actingAs($cajero);
        $this->abrirTurno($cajero);
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
            'clave_idempotencia' => 'venta-turno-cerrado',
        ])->assertOk();

        $venta = Venta::first();
        TurnoCaja::where('usuario_id', $cajero->id)->update(['estado' => 'cerrada', 'cerrado_en' => now()]);

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Intento tras cerrar la caja',
            'metodo' => 'efectivo',
            'items' => [$venta->detalles->first()->id => 1],
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseCount('reembolsos', 0);
    }

    public function test_venta_en_efectivo_registra_el_cambio_como_retiro(): void
    {
        $usuario = $this->cajero();
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
            'pagado' => '25.00',
            'clave_idempotencia' => 'cambio-efectivo-test',
        ])->assertOk();

        $venta = Venta::first();

        $this->assertDatabaseHas('movimientos_efectivo', [
            'tipo' => 'venta',
            'monto' => 25,
        ]);
        $this->assertDatabaseHas('movimientos_efectivo', [
            'tipo' => 'retiro',
            'monto' => -5,
            'motivo' => 'Cambio de venta ' . $venta->numero_comprobante,
        ]);

        $turno = $this->turnoActual($usuario);
        $this->assertSame(20.0, (float) $turno->movimientosEfectivo()->sum('monto'));
    }

    public function test_idempotencia_entre_usuarios_devuelve_la_venta_existente(): void
    {
        $primerCajero = $this->cajero();
        $this->actingAs($primerCajero);
        $this->abrirTurno($primerCajero);
        $product = $this->product(price: 10, stock: 5);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'clave-compartida',
        ])->assertOk();
        $ventaOriginal = Venta::first();

        $segundoCajero = $this->cajero();
        $this->actingAs($segundoCajero);
        $this->abrirTurno($segundoCajero);

        $response = $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '10.00',
            'clave_idempotencia' => 'clave-compartida',
        ]);

        $response->assertOk()->assertJsonPath('sale.numero_comprobante', $ventaOriginal->numero_comprobante);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_descuento_de_producto_mayor_al_100_no_genera_totales_negativos(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);

        $categoria = Category::create(['nombre' => 'Pruebas']);
        $producto = Product::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto con descuento desmedido',
            'precio' => 10,
            'descuento' => 150,
            'existencias' => 10,
            'esta_activo' => true,
        ]);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
            'descuento_activo' => true,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '0.00',
            'clave_idempotencia' => 'descuento-150-test',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('0.00', number_format($venta->total, 2));
        $this->assertSame('10.00', number_format($venta->descuento, 2));
        $this->assertSame('0.00', number_format($venta->pagado, 2));
    }

    public function test_variante_con_stock_no_se_decrementa_si_el_producto_no_maneja_existencias(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);

        $categoria = Category::create(['nombre' => 'Pruebas']);
        $producto = Product::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Servicio con variante',
            'precio' => 5,
            'existencias' => 0,
            'maneja_existencias' => false,
            'esta_activo' => true,
        ]);
        $variante = ProductoVariante::create([
            'producto_id' => $producto->id,
            'nombre' => 'Grande',
            'precio' => 5,
            'stock' => 2,
            'esta_activo' => true,
        ]);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'variante_id' => $variante->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => '5.00',
            'clave_idempotencia' => 'variante-sin-existencias-test',
        ])->assertOk();

        $this->assertSame(2, (int) $variante->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_reembolso_en_credito_es_viable_sin_movimiento_de_efectivo(): void
    {
        $cajero = $this->cajero();
        $this->actingAs($cajero);
        $this->abrirTurno($cajero);
        $product = $this->product(price: 10, stock: 3);
        $cliente = Cliente::create(['nombre' => 'Cliente fiado', 'esta_activo' => true]);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 2]],
            'metodo_pago' => 'credito',
            'pagado' => '0.00',
            'cliente_id' => $cliente->id,
            'descripcion_cliente' => 'Fiado a la casa',
            'clave_idempotencia' => 'venta-credito-reembolso',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame(0.0, (float) $venta->pagado);

        $this->actingAs($this->adminBar());

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'total',
            'motivo' => 'Se perdona la deuda',
            'metodo' => 'credito',
            'items' => [$venta->detalles->first()->id => 2],
        ])->assertRedirect();

        $this->assertDatabaseHas('reembolsos', [
            'venta_id' => $venta->id,
            'tipo' => 'total',
            'metodo' => 'credito',
            'monto' => 20,
        ]);
        $this->assertDatabaseCount('movimientos_efectivo', 0);
    }

    public function test_reembolso_incluye_impuesto_proporcional(): void
    {
        $cajero = $this->cajero();
        $this->actingAs($cajero);
        $this->abrirTurno($cajero);
        $product = $this->product(price: 10, stock: 4);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => true,
            'porcentaje_impuesto' => 15,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '23.00',
            'clave_idempotencia' => 'venta-iva-reembolso',
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('23.00', number_format($venta->total, 2));

        $this->actingAs($this->adminBar());

        $this->post(route('reembolsos.crear', $venta), [
            'tipo' => 'parcial',
            'motivo' => 'Devolución de una unidad',
            'metodo' => 'efectivo',
            'items' => [$venta->detalles->first()->id => 1],
        ])->assertRedirect();

        $this->assertDatabaseHas('reembolsos', [
            'venta_id' => $venta->id,
            'monto' => 11.5,
        ]);
    }

    public function test_efectivo_esperado_excluye_transferencias(): void
    {
        $usuario = $this->cajero();
        $this->actingAs($usuario);
        $this->abrirTurno($usuario);
        $product = $this->product(price: 10, stock: 10);
        BusinessSetting::create([
            'nombre_negocio' => 'Prueba',
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 1]],
            'metodo_pago' => 'transferencia',
            'pagado' => '10.00',
            'entidad_financiera' => 'Banco Test',
            'numero_comprobante_pago' => 'TRF-001',
            'clave_idempotencia' => 'transferencia-esperado',
        ])->assertOk();

        $this->postJson(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $product->id, 'cantidad' => 2]],
            'metodo_pago' => 'efectivo',
            'pagado' => '20.00',
            'clave_idempotencia' => 'efectivo-esperado',
        ])->assertOk();

        $this->post(route('caja.cerrar'), [
            'es_final' => '1',
            'billetes' => [20 => 1, 100 => 0, 50 => 0, 10 => 0, 5 => 0, 1 => 0],
            'monedas' => [1 => 0, 0.50 => 0, 0.25 => 0, 0.10 => 0, 0.05 => 0, 0.01 => 0],
        ])->assertRedirect();

        $turno = TurnoCaja::where('usuario_id', $usuario->id)->first();
        $this->assertSame(20.0, (float) $turno->fresh()->efectivo_esperado);
    }

    public function test_aprobar_cuadre_con_diferencia_material_exige_motivo(): void
    {
        $admin = $this->adminBar();
        $this->actingAs($admin);

        $turno = TurnoCaja::create([
            'negocio_id' => Negocio::first()->id,
            'usuario_id' => $admin->id,
            'caja_id' => Caja::create(['nombre' => 'Caja 1', 'esta_activa' => true])->id,
            'fondo_inicial' => 0,
            'abierto_en' => now(),
            'cerrado_en' => now(),
            'efectivo_esperado' => 100,
            'efectivo_contado' => 105,
            'diferencia' => 5,
            'estado' => 'pendiente_aprobacion',
        ]);

        $this->post(route('cuadres.aprobar', $turno))->assertSessionHasErrors('motivo');
        $this->assertSame('pendiente_aprobacion', $turno->fresh()->estado);

        $this->post(route('cuadres.aprobar', $turno), ['motivo' => 'Sobrante por redondeo'])
            ->assertRedirect();
        $this->assertSame('aprobada', $turno->fresh()->estado);
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

        $usuario = User::factory()->create();
        \App\Models\MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'propietario',
            'esta_activa' => true,
        ]);

        return $usuario;
    }
}
