<?php

namespace Tests\Feature;

use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Plan;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Models\TurnoCaja;
use App\Models\Caja;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CajerosTest extends TestCase
{
    use RefreshDatabase;

    private function barConPlan(int $limiteCajeros = 2): Negocio
    {
        $plan = Plan::create(['nombre' => 'Básico', 'duracion_dias' => 30, 'limite_cajeros' => $limiteCajeros, 'limite_cajas' => 1, 'limite_sucursales' => 1]);
        $negocio = Negocio::create(['nombre' => 'Bar', 'identificador' => 'bar-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);
        Sucursal::create(['nombre' => 'Principal']);

        Membresia::create([
            'negocio_id' => $negocio->id,
            'plan_id' => $plan->id,
            'estado' => 'activa',
            'fecha_inicio' => now(),
            'fecha_vencimiento' => now()->addDays(30),
        ]);

        return $negocio;
    }

    private function propietario(Negocio $negocio): User
    {
        $admin = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $admin->id, 'rol' => 'propietario', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $admin;
    }

    private function adminBar(Negocio $negocio): User
    {
        $admin = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $admin->id, 'rol' => 'admin_bar', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $admin;
    }

    private function cajero(Negocio $negocio): User
    {
        $cajero = User::factory()->create(['pin' => Hash::make('1234')]);
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        return $cajero;
    }

    public function test_propietario_puede_ver_la_lista_de_cajeros(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->propietario($negocio);
        $this->cajero($negocio);

        $this->actingAs($admin);

        $this->get(route('cajeros.index'))->assertOk()->assertSee('Cajeros');
    }

    public function test_admin_bar_puede_ver_cajeros_pero_no_gestionarlos(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->adminBar($negocio);
        $sucursal = Sucursal::where('negocio_id', $negocio->id)->first();

        $this->actingAs($admin);

        $this->get(route('cajeros.index'))->assertOk();
        $this->post(route('cajeros.store'), [
            'nombre' => 'Nuevo',
            'correo' => 'nuevo@bar.com',
            'clave' => 'secreto123',
            'pin' => '1234',
            'sucursal_id' => $sucursal->id,
        ])->assertForbidden();
    }

    public function test_cajero_no_puede_acceder_al_backoffice(): void
    {
        $negocio = $this->barConPlan();
        $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);

        $this->actingAs($cajero);

        $this->get(route('cajeros.index'))->assertForbidden();
        $this->get(route('productos.index'))->assertForbidden();
        $this->get(route('configuracion.negocio'))->assertForbidden();
    }

    public function test_cajero_puede_acceder_al_punto_de_venta(): void
    {
        $negocio = $this->barConPlan();
        $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);

        $this->actingAs($cajero);

        $this->get(route('punto_venta.inicio'))->assertOk();
    }

    public function test_propietario_puede_crear_un_cajero_con_pin(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::where('negocio_id', $negocio->id)->first();

        $this->actingAs($admin);

        $this->post(route('cajeros.store'), [
            'nombre' => 'María',
            'correo' => 'maria@bar.com',
            'clave' => 'secreto123',
            'pin' => '5678',
            'sucursal_id' => $sucursal->id,
        ])->assertRedirect(route('cajeros.index'));

        $usuario = User::where('correo', 'maria@bar.com')->firstOrFail();
        $this->assertNotSame('5678', $usuario->pin);
        $this->assertTrue(Hash::check('5678', $usuario->pin));
        $this->assertDatabaseHas('membresias_negocio', ['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'cajero', 'esta_activa' => true]);
    }

    public function test_no_se_excede_el_limite_de_cajeros_del_plan(): void
    {
        $negocio = $this->barConPlan(1);
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::where('negocio_id', $negocio->id)->first();
        $this->cajero($negocio);
        MembresiaNegocio::where('negocio_id', $negocio->id)->where('rol', 'cajero')->update(['sucursal_id' => $sucursal->id]);

        $this->actingAs($admin);

        $this->post(route('cajeros.store'), [
            'nombre' => 'Segundo',
            'correo' => 'segundo@bar.com',
            'clave' => 'secreto123',
            'pin' => '1234',
            'sucursal_id' => $sucursal->id,
        ])->assertStatus(422);
    }

    public function test_propietario_puede_desactivar_un_cajero_sin_borrarlo(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);

        $this->actingAs($admin);

        $this->delete(route('cajeros.destroy', $cajero))->assertRedirect(route('cajeros.index'));

        $this->assertDatabaseHas('membresias_negocio', ['negocio_id' => $negocio->id, 'usuario_id' => $cajero->id, 'esta_activa' => false]);
        $this->assertDatabaseHas('usuarios', ['id' => $cajero->id, 'esta_activo' => true]);
    }

    public function test_cajero_desactivado_no_puede_acceder_al_pos(): void
    {
        $negocio = $this->barConPlan();
        $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);

        MembresiaNegocio::where('usuario_id', $cajero->id)->where('negocio_id', $negocio->id)->update(['esta_activa' => false]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($cajero);

        $this->get(route('punto_venta.inicio'))->assertForbidden();
    }

    public function test_el_reporte_por_cajero_agrupa_las_ventas(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);

        app(ContextoNegocio::class)->establecer($negocio->id);

        Venta::create([
            'numero_comprobante' => 'CMP-000001',
            'usuario_id' => $cajero->id,
            'subtotal' => 10,
            'impuesto' => 1.5,
            'impuesto_habilitado' => true,
            'porcentaje_impuesto' => 15,
            'total' => 11.5,
            'metodo_pago' => 'efectivo',
            'pagado' => 11.5,
            'cambio' => 0,
        ]);
        Venta::create([
            'numero_comprobante' => 'CMP-000002',
            'usuario_id' => $cajero->id,
            'subtotal' => 20,
            'impuesto' => 0,
            'impuesto_habilitado' => false,
            'porcentaje_impuesto' => 0,
            'total' => 20,
            'metodo_pago' => 'transferencia',
            'pagado' => 20,
            'cambio' => 0,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('reportes.cajeros'))->assertOk();
        $response->assertSee($cajero->nombre);
        $response->assertSee('31.50');
        $response->assertSee('2');
    }

    public function test_propietario_reabre_un_turno_cerrado(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);
        $caja = Caja::create(['nombre' => 'Caja 1', 'esta_activa' => true, 'negocio_id' => $negocio->id]);

        $turno = TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'efectivo_esperado' => 100,
            'efectivo_contado' => 100,
            'diferencia' => 0,
            'estado' => 'cerrada',
            'negocio_id' => $negocio->id,
        ]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($admin);

        $this->post(route('caja.reabrir', $turno))->assertRedirect();

        $turno->refresh();
        $this->assertSame('abierta', $turno->estado);
        $this->assertNull($turno->cerrado_en);
        $this->assertStringContainsString('Reabierto por', $turno->notas);
    }

    public function test_admin_bar_no_puede_reabrir_un_turno(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);
        $caja = Caja::create(['nombre' => 'Caja 1', 'esta_activa' => true, 'negocio_id' => $negocio->id]);

        $turno = TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'estado' => 'cerrada',
            'negocio_id' => $negocio->id,
        ]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($admin);

        $this->post(route('caja.reabrir', $turno))->assertForbidden();
    }

    public function test_cajero_no_puede_reabrir_un_turno(): void
    {
        $negocio = $this->barConPlan();
        $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);
        $caja = Caja::create(['nombre' => 'Caja 1', 'esta_activa' => true, 'negocio_id' => $negocio->id]);

        $turno = TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'estado' => 'cerrada',
            'negocio_id' => $negocio->id,
        ]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($cajero);

        $this->post(route('caja.reabrir', $turno))->assertForbidden();
    }

    public function test_el_reporte_de_arqueos_muestra_diferencias(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);
        $caja = Caja::create(['nombre' => 'Caja 1', 'esta_activa' => true, 'negocio_id' => $negocio->id]);

        TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'efectivo_esperado' => 150,
            'efectivo_contado' => 148.5,
            'diferencia' => -1.50,
            'estado' => 'cerrada',
            'negocio_id' => $negocio->id,
        ]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($admin);

        $this->get(route('caja.reporte'))->assertOk()->assertSee('Arqueos de caja');
    }

    public function test_admin_bar_aprueba_un_cuadre_pendiente(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);
        $caja = Caja::create(['nombre' => 'Caja 1', 'esta_activa' => true, 'negocio_id' => $negocio->id]);

        $turno = TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'efectivo_esperado' => 120,
            'efectivo_contado' => 120,
            'diferencia' => 0,
            'estado' => 'pendiente_aprobacion',
            'negocio_id' => $negocio->id,
        ]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($admin);

        $this->post(route('cuadres.aprobar', $turno))->assertRedirect();

        $turno->refresh();
        $this->assertSame('aprobada', $turno->estado);
        $this->assertSame($admin->id, $turno->aprobado_por);
    }

    public function test_admin_bar_puede_ver_el_reporte_por_cajero(): void
    {
        $negocio = $this->barConPlan();
        $admin = $this->adminBar($negocio);
        $cajero = $this->cajero($negocio);
        $sucursal = Sucursal::where('negocio_id', $negocio->id)->first();

        Venta::create([
            'numero_comprobante' => 'CMP-000003',
            'usuario_id' => $cajero->id,
            'subtotal' => 20,
            'impuesto' => 0,
            'impuesto_habilitado' => false,
            'porcentaje_impuesto' => 0,
            'total' => 20,
            'metodo_pago' => 'efectivo',
            'pagado' => 20,
            'cambio' => 0,
            'sucursal_id' => $sucursal->id ?? null,
        ]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($admin);

        $this->get(route('reportes.cajeros'))->assertOk()->assertSee($cajero->nombre);
    }
}