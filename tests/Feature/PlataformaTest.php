<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Plan;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlataformaTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_membresia_sigue_vigente_el_dia_de_su_vencimiento(): void
    {
        $membresia = new Membresia(['estado' => 'activa', 'fecha_vencimiento' => now()->toDateString()]);
        $this->assertTrue($membresia->estaVigente());
        $this->assertFalse($membresia->estaVencida());

        $vencida = new Membresia(['estado' => 'activa', 'fecha_vencimiento' => now()->subDay()->toDateString()]);
        $this->assertFalse($vencida->estaVigente());
        $this->assertTrue($vencida->estaVencida());
    }

    public function test_renovar_rechaza_membresias_suspendidas_y_canceladas(): void
    {
        $this->actingAs($this->superAdmin());

        $suspendida = $this->crearBarConMembresia([], ['estado' => 'suspendida', 'fecha_vencimiento' => now()->addDays(30)]);
        $this->post(route('plataforma.negocios.membresia.renovar', $suspendida))->assertStatus(422);
        $this->assertSame('suspendida', $suspendida->membresia->refresh()->estado);

        $cancelada = $this->crearBarConMembresia([], ['estado' => 'cancelada', 'fecha_vencimiento' => now()->addDays(30)]);
        $this->post(route('plataforma.negocios.membresia.renovar', $cancelada))->assertStatus(422);
        $this->assertSame('cancelada', $cancelada->membresia->refresh()->estado);
    }

    public function test_un_bar_no_puede_tener_dos_contratos_activos(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConMembresia();

        $payload = [
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addYear()->toDateString(),
            'forma_contratacion' => 'mensual',
        ];

        $this->post(route('plataforma.negocios.contratos.store', $negocio), $payload)->assertRedirect();
        $this->post(route('plataforma.negocios.contratos.store', $negocio), $payload)->assertStatus(422);

        $this->assertSame(1, \App\Models\Contrato::where('negocio_id', $negocio->id)->where('estado', 'activo')->count());
    }

    public function test_eliminar_bar_desactiva_membresias_y_cancela_contratos(): void
    {
        $this->actingAs($this->superAdmin());

        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar a eliminar',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'numero_sucursales_contratadas' => 1,
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'eliminar@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
        ])->assertRedirect();

        $negocio = Negocio::where('identificador', 'bar-a-eliminar')->firstOrFail();

        $this->delete(route('plataforma.negocios.destroy', $negocio))->assertRedirect();

        $this->assertSoftDeleted('negocios', ['id' => $negocio->id]);
        $this->assertDatabaseHas('membresias_negocio', ['negocio_id' => $negocio->id, 'esta_activa' => false]);
        $this->assertDatabaseHas('contratos', ['negocio_id' => $negocio->id, 'estado' => 'cancelado']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['rol' => 'super_admin']);
    }

    private function crearBarConMembresia(array $atributos = [], array $membresia = []): Negocio
    {
        $plan = Plan::create(['nombre' => 'Prueba', 'duracion_dias' => 30, 'limite_cajeros' => 2, 'limite_cajas' => 1, 'limite_sucursales' => 1]);

        $negocio = Negocio::create(array_merge([
            'nombre' => 'Bar de prueba',
            'identificador' => 'bar-prueba-' . str()->random(6),
            'esta_activo' => true,
        ], $atributos));

        app(ContextoNegocio::class)->establecer($negocio->id);

        Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);

        Membresia::create(array_merge([
            'negocio_id' => $negocio->id,
            'plan_id' => $plan->id,
            'estado' => 'activa',
            'fecha_inicio' => now()->subDay(),
            'fecha_vencimiento' => now()->addDays(30),
        ], $membresia));

        return $negocio;
    }

    public function test_super_admin_ve_la_lista_de_bares(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('plataforma.negocios.index'))->assertOk()->assertSee('Bares registrados');
    }

    public function test_usuario_del_bar_no_puede_gestionar_bares(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('plataforma.negocios.index'))->assertForbidden();
    }

    public function test_super_admin_crea_un_bar_con_admin_inicial(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);

        $this->actingAs($this->superAdmin());

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar San Felipe',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'numero_sucursales_contratadas' => 2,
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'dueno@bar.com',
            'cedula_admin' => '1002003000',
            'celular_admin' => '0964142527',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'nombre_sucursal' => 'Central',
            'n_cajeros_sucursal' => 2,
        ])->assertRedirect();

        $negocio = Negocio::where('identificador', 'bar-san-felipe')->firstOrFail();
        $this->get(route('plataforma.negocios.show', $negocio))->assertOk();
        $this->assertSame('Bar San Felipe', $negocio->nombre);
        $this->assertSame('prueba', $negocio->membresia->estado ?? 'no-registrada');
        $this->assertDatabaseHas('sucursales', ['negocio_id' => $negocio->id, 'nombre' => 'Central']);
        $this->assertDatabaseHas('usuarios', ['correo' => 'dueno@bar.com']);
        $this->assertDatabaseHas('membresias_negocio', ['negocio_id' => $negocio->id, 'usuario_id' => User::where('correo', 'dueno@bar.com')->first()->id, 'rol' => 'propietario']);
        $this->assertDatabaseHas('configuraciones_negocio', ['negocio_id' => $negocio->id]);
        $this->assertDatabaseHas('contratos', ['negocio_id' => $negocio->id, 'estado' => 'activo']);
    }

    public function test_creacion_de_bar_genera_identificadores_unicos(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);

        $this->actingAs($this->superAdmin());

        $base = [
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'numero_sucursales_contratadas' => 1,
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
        ];

        $this->post(route('plataforma.negocios.store'), array_merge($base, ['nombre' => 'Mi Bar', 'correo_admin' => 'a@b.com', 'nombre_admin' => 'A']))->assertRedirect();
        $this->post(route('plataforma.negocios.store'), array_merge($base, ['nombre' => 'Mi Bar', 'correo_admin' => 'b@c.com', 'nombre_admin' => 'B']))->assertRedirect();

        $ids = Negocio::pluck('identificador')->filter(fn ($i) => str_starts_with((string) $i, 'mi-bar'))->values()->all();
        $this->assertCount(2, $ids);
        $this->assertSame('mi-bar', $ids[0]);
        $this->assertSame('mi-bar-1', $ids[1]);
    }

    public function test_creacion_de_bar_rechaza_ruc_duplicado(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);

        $this->actingAs($this->superAdmin());

        $base = [
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'numero_sucursales_contratadas' => 1,
            'nombre_admin' => 'A',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'ruc' => '1002003000001',
        ];

        $this->post(route('plataforma.negocios.store'), array_merge($base, ['nombre' => 'Uno', 'correo_admin' => 'a@b.com']))->assertRedirect();
        $this->post(route('plataforma.negocios.store'), array_merge($base, ['nombre' => 'Dos', 'correo_admin' => 'b@c.com']))->assertSessionHasErrors('ruc');

        $this->assertSame(1, Negocio::where('ruc', '1002003000001')->count());
    }

    public function test_downgrade_de_plan_bloqueado_con_limites_excedidos(): void
    {
        $planBasico = Plan::create(['nombre' => 'Básico', 'duracion_dias' => 30, 'limite_cajeros' => 1, 'limite_cajas' => 0, 'limite_sucursales' => 0]);

        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConMembresia();

        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => User::factory()->create()->id, 'rol' => 'cajero', 'esta_activa' => true]);
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => User::factory()->create()->id, 'rol' => 'cajero', 'esta_activa' => true]);
        Caja::create(['negocio_id' => $negocio->id, 'nombre' => 'Caja 1', 'esta_activa' => true]);

        $planAnterior = $negocio->membresia->plan_id;

        $this->put(route('plataforma.negocios.update', $negocio), [
            'nombre' => $negocio->nombre,
            'numero_sucursales_contratadas' => 1,
            'plan_id' => $planBasico->id,
        ])->assertSessionHasErrors('plan_id');

        $this->assertSame($planAnterior, $negocio->membresia->refresh()->plan_id);
    }

    public function test_mismo_plan_no_bloquea_la_actualizacion(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConMembresia();

        $this->put(route('plataforma.negocios.update', $negocio), [
            'nombre' => 'Bar renombrado',
            'numero_sucursales_contratadas' => 1,
            'plan_id' => $negocio->membresia->plan_id,
        ])->assertRedirect();

        $this->assertSame('Bar renombrado', $negocio->fresh()->nombre);
    }

    public function test_renovar_extiende_la_membresia(): void
    {
        $this->actingAs($this->superAdmin());

        $negocio = $this->crearBarConMembresia();
        $fechaAnterior = $negocio->membresia->fecha_vencimiento;

        $this->post(route('plataforma.negocios.membresia.renovar', $negocio))->assertRedirect();

        $negocio->membresia->refresh();
        $this->assertTrue($negocio->membresia->fecha_vencimiento->greaterThan($fechaAnterior));
        $this->assertSame('activa', $negocio->membresia->estado);
    }

    public function test_suspender_y_reactivar_membresia(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConMembresia();

        $this->post(route('plataforma.negocios.membresia.suspender', $negocio))->assertRedirect();
        $this->assertSame('suspendida', $negocio->membresia->refresh()->estado);

        $this->post(route('plataforma.negocios.membresia.reactivar', $negocio))->assertRedirect();
        $this->assertSame('activa', $negocio->membresia->refresh()->estado);
    }

    public function test_bar_suspendido_bloquea_el_acceso_del_tenant(): void
    {
        $negocio = $this->crearBarConMembresia(['esta_activo' => false]);
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('punto_venta.inicio'))->assertForbidden();
    }

    public function test_membresia_vencida_bloquea_el_acceso_del_tenant(): void
    {
        $negocio = $this->crearBarConMembresia([], ['estado' => 'activa', 'fecha_vencimiento' => now()->subDay()]);
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('punto_venta.inicio'))->assertForbidden();
    }

    public function test_el_selector_muestra_los_bares_del_usuario(): void
    {
        $negocioUno = $this->crearBarConMembresia();
        $negocioDos = $this->crearBarConMembresia();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocioUno->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);
        MembresiaNegocio::create(['negocio_id' => $negocioDos->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('negocio.seleccionar'))->assertOk()->assertSee('Selecciona tu bar');

        $this->post(route('negocio.seleccionar.guardar'), ['negocio_id' => $negocioDos->id])
            ->assertRedirect(route('panel.inicio'));

        $this->assertSame((int) $negocioDos->id, (int) session('negocio_id'));
    }

    public function test_admin_bar_con_un_solo_bar_va_al_panel(): void
    {
        $negocio = $this->crearBarConMembresia();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('negocio.seleccionar'))->assertRedirect(route('panel.inicio'));
    }

    public function test_un_cajero_con_un_solo_bar_va_al_pos(): void
    {
        $negocio = $this->crearBarConMembresia();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('negocio.seleccionar'))->assertRedirect(route('punto_venta.inicio'));
    }

    public function test_rol_legacy_administrador_va_al_panel(): void
    {
        $negocio = $this->crearBarConMembresia();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'administrador', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('punto_venta.inicio'))->assertRedirect(route('panel.inicio'));
        $this->get(route('panel.inicio'))->assertOk();
    }
}