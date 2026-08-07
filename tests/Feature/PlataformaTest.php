<?php

namespace Tests\Feature;

use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Plan;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlataformaTest extends TestCase
{
    use RefreshDatabase;

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

        Sucursal::create(['negocio_id' => $negocio->id, 'nombre' => 'Principal', 'esta_activa' => true]);

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
        $this->actingAs(User::factory()->create(['rol' => 'cajero']));

        $this->get(route('plataforma.negocios.index'))->assertForbidden();
    }

    public function test_super_admin_crea_un_bar_con_admin_inicial(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);

        $this->actingAs($this->superAdmin());

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar San Felipe',
            'identificador' => 'bar-san-felipe',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'dueno@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'nombre_sucursal' => 'Central',
        ])->assertRedirect(route('plataforma.negocios.index'));

        $negocio = Negocio::where('identificador', 'bar-san-felipe')->firstOrFail();
        $this->assertSame('Bar San Felipe', $negocio->nombre);
        $this->assertSame('prueba', $negocio->membresia->estado ?? 'no-registrada');
        $this->assertDatabaseHas('sucursales', ['negocio_id' => $negocio->id, 'nombre' => 'Central']);
        $this->assertDatabaseHas('usuarios', ['correo' => 'dueno@bar.com', 'rol' => 'propietario']);
        $this->assertDatabaseHas('membresias_negocio', ['negocio_id' => $negocio->id, 'usuario_id' => User::where('correo', 'dueno@bar.com')->first()->id, 'rol' => 'propietario']);
        $this->assertDatabaseHas('configuraciones_negocio', ['negocio_id' => $negocio->id]);
    }

    public function test_creacion_de_bar_valida_identificador_unico(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);

        $this->actingAs($this->superAdmin());

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Uno',
            'identificador' => 'repetido',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'nombre_admin' => 'A',
            'correo_admin' => 'a@b.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
        ])->assertSessionHasNoErrors();

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Dos',
            'identificador' => 'repetido',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'nombre_admin' => 'B',
            'correo_admin' => 'b@c.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
        ])->assertSessionHasErrors('identificador');

        $this->assertSame(1, Negocio::where('identificador', 'repetido')->count());
        $this->assertSame(1, Negocio::where('identificador', 'repetido')->where('nombre', 'Uno')->count());
    }

    public function test_renovar_extiende_la_membresia(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConMembresia();
        $fechaAnterior = $negocio->membresia->fecha_vencimiento;

        $this->get(route('plataforma.negocios.membresia.renovar', $negocio))->assertRedirect();

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
            ->assertRedirect(route('punto_venta.inicio'));

        $this->assertSame((int) $negocioDos->id, (int) session('negocio_id'));
    }

    public function test_un_solo_bar_redirige_directo_al_pos(): void
    {
        $negocio = $this->crearBarConMembresia();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('negocio.seleccionar'))->assertRedirect(route('punto_venta.inicio'));
    }
}