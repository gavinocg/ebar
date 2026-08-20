<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ConfiguracionNegocio;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\TurnoCajero;
use App\Models\User;
use App\Services\ContextoNegocio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->negocio = $this->bar();
        app(ContextoNegocio::class)->establecer($this->negocio->id);
        ConfiguracionNegocio::create([
            'negocio_id' => $this->negocio->id,
            'nombre_negocio' => $this->negocio->nombre,
            'cobrar_impuesto' => false,
            'porcentaje_impuesto' => 0,
        ]);
    }

    private function bar(): Negocio
    {
        $negocio = Negocio::create(['nombre' => 'Bar CA', 'identificador' => 'bar-ca-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $negocio;
    }

    private function admin(Negocio $negocio): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'propietario', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);
        return $usuario;
    }

    private function cajero(Negocio $negocio, array $config = ['cuadre_activo' => true, 'aprobacion_activa' => true]): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'cajero',
            'esta_activa' => true,
            'cuadre_activo' => $config['cuadre_activo'] ?? true,
            'aprobacion_activa' => $config['aprobacion_activa'] ?? true,
        ]);
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

    private function abrirTurno(User $cajero): TurnoCajero
    {
        return TurnoCajero::create([
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);
    }

    private function ventaEfectivo(User $cajero, Producto $producto): void
    {
        $this->actingAs($cajero);
        $this->post(route('punto_venta.cobrar'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo',
            'pagado' => (string) $producto->precio,
            'clave_idempotencia' => 'caja-' . str()->random(8),
        ])->assertOk();
    }

    private function cierreFinal(User $cajero, array $data = []): void
    {
        $default = [
            'es_final' => true,
            'billetes' => [20 => 1, 5 => 1],
            'monedas' => [1 => 0],
            'notas' => 'Cierre',
        ];
        $this->actingAs($cajero);
        $this->post(route('caja.cerrar'), array_merge($default, $data))->assertRedirect();
    }

    public function test_cierre_final_con_cuadre_y_aprobacion_activa_pone_pendiente_aprobacion(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);

        $this->cierreFinal($cajero);

        $turno->refresh();
        $this->assertSame('pendiente_aprobacion', $turno->estado);
        $this->assertNotNull($turno->efectivo_esperado);
        $this->assertNotNull($turno->efectivo_contado);
        $this->assertNotNull($turno->diferencia);
    }

    public function test_cierre_final_con_cuadre_activo_sin_aprobacion_aprueba_directo(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => false]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);

        $this->cierreFinal($cajero);

        $turno->refresh();
        $this->assertSame('aprobada', $turno->estado);
    }

    public function test_cierre_temporal_sin_cuadre_pone_cerrada(): void
    {
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => false, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);

        $this->actingAs($cajero);
        $this->post(route('caja.cerrar'), ['es_final' => false, 'notas' => 'Temporal'])->assertRedirect();

        $turno->refresh();
        $this->assertSame('cerrada', $turno->estado);
    }

    public function test_admin_puede_aprobar_cuadre_pendiente(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);
        $this->cierreFinal($cajero);
        $turno->refresh();
        $this->assertSame('pendiente_aprobacion', $turno->estado);

        $this->actingAs($admin);
        $this->post(route('cuadres.aprobar', $turno))->assertRedirect()->assertSessionHas('success', 'Cuadre aprobado correctamente.');

        $turno->refresh();
        $this->assertSame('aprobada', $turno->estado);
        $this->assertSame($admin->id, $turno->aprobado_por);
        $this->assertNotNull($turno->aprobado_en);
    }

    public function test_admin_puede_rechazar_cuadre_pendiente_con_motivo(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);
        $this->cierreFinal($cajero);
        $turno->refresh();

        $this->actingAs($admin);
        $this->post(route('cuadres.rechazar', $turno), ['motivo' => 'Falta dinero'])
            ->assertRedirect()->assertSessionHas('success', 'Cuadre rechazado. El cajero puede realizar un nuevo cierre.');

        $turno->refresh();
        $this->assertSame('abierta', $turno->estado);
        $this->assertNull($turno->efectivo_contado);
        $this->assertNull($turno->diferencia);
    }

    public function test_rechazar_cuadre_requiere_motivo(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);
        $this->cierreFinal($cajero);
        $turno->refresh();

        $this->actingAs($admin);
        $this->post(route('cuadres.rechazar', $turno), ['motivo' => ''])->assertSessionHasErrors('motivo');
    }

    public function test_cajero_puede_solicitar_modificacion_de_cuadre_aprobado(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);
        $this->cierreFinal($cajero);
        $turno->refresh();

        $this->actingAs($admin);
        $this->post(route('cuadres.aprobar', $turno));
        $turno->refresh();
        $this->assertSame('aprobada', $turno->estado);

        $this->actingAs($cajero);
        $this->post(route('cuadres.solicitar-modificacion', $turno), ['motivo' => 'Error en conteo'])
            ->assertRedirect()->assertSessionHas('success', 'Solicitud de modificación enviada al administrador.');

        $turno->refresh();
        $this->assertSame('pendiente_modificacion', $turno->estado);
    }

    public function test_admin_puede_autorizar_modificacion(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);
        $this->cierreFinal($cajero);
        $turno->refresh();

        $this->actingAs($admin);
        $this->post(route('cuadres.aprobar', $turno));
        $turno->refresh();

        $this->actingAs($cajero);
        $this->post(route('cuadres.solicitar-modificacion', $turno), ['motivo' => 'Error']);
        $turno->refresh();
        $this->assertSame('pendiente_modificacion', $turno->estado);

        $this->actingAs($admin);
        $this->post(route('cuadres.autorizar-modificacion', $turno))->assertRedirect()->assertSessionHas('success', 'Modificación autorizada. El cajero puede realizar un nuevo cierre.');

        $turno->refresh();
        $this->assertSame('abierta', $turno->estado);
        $this->assertNull($turno->efectivo_contado);
        $this->assertNull($turno->diferencia);
    }

    public function test_admin_puede_reabrir_turno_cerrado(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => false, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);

        $this->actingAs($cajero);
        $this->post(route('caja.cerrar'), ['es_final' => false, 'notas' => 'Temporal'])->assertRedirect();
        $turno->refresh();
        $this->assertSame('cerrada', $turno->estado);

        $this->actingAs($admin);
        $this->post(route('caja.reabrir', $turno))->assertRedirect()->assertSessionHas('success', 'Turno reabierto correctamente.');

        $turno->refresh();
        $this->assertSame('abierta', $turno->estado);
        $this->assertNull($turno->cerrado_en);
    }

    public function test_cajero_no_puede_aprobar_cuadres(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $cajero2 = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);
        $this->cierreFinal($cajero);
        $turno->refresh();

        $this->actingAs($cajero2);
        $this->post(route('cuadres.aprobar', $turno))->assertStatus(403);
    }

    public function test_no_se_puede_aprobar_cuadre_de_otro_negocio(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto);
        $this->cierreFinal($cajero);
        $turno->refresh();

        $negocio2 = Negocio::create(['nombre' => 'Bar 2', 'identificador' => 'bar-2-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio2->id);
        $admin2 = $this->admin($negocio2);

        app(ContextoNegocio::class)->establecer($this->negocio->id);
        $this->actingAs($admin2);
        $this->post(route('cuadres.aprobar', $turno))->assertStatus(404);
    }

    public function test_aprobar_cuadre_con_diferencia_mayor_a_1_requiere_motivo(): void
    {
        $admin = $this->admin($this->negocio);
        $cajero = $this->cajero($this->negocio, ['cuadre_activo' => true, 'aprobacion_activa' => true]);
        $producto = $this->producto($this->negocio, 'Prod', 25);

        $turno = $this->abrirTurno($cajero);
        $this->ventaEfectivo($cajero, $producto); // esperado 25

        // Cierre con contado 50 (diferencia 25 > 1)
        $this->actingAs($cajero);
        $this->post(route('caja.cerrar'), [
            'es_final' => true,
            'billetes' => [50 => 1],
            'monedas' => [1 => 0],
            'notas' => 'Cierre con diff',
        ])->assertRedirect();
        $turno->refresh();
        $this->assertSame('pendiente_aprobacion', $turno->estado);
        $this->assertEquals(25.0, (float) $turno->diferencia);

        $this->actingAs($admin);
        $this->post(route('cuadres.aprobar', $turno), ['motivo' => ''])->assertSessionHasErrors('motivo');

        $this->post(route('cuadres.aprobar', $turno), ['motivo' => 'Error humano'])->assertRedirect()->assertSessionHas('success');
    }
}
