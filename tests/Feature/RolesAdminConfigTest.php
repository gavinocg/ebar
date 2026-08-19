<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\ConfiguracionNegocio;
use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAdminConfigTest extends TestCase
{
    use RefreshDatabase;

    private function bar(): Negocio
    {
        $plan = Plan::create(['nombre' => 'Básico', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 5, 'limite_sucursales' => 5]);
        $negocio = Negocio::create(['nombre' => 'Bar S', 'identificador' => 'bar-s-' . str()->random(6), 'esta_activo' => true, 'numero_sucursales_contratadas' => 5]);
        app(ContextoNegocio::class)->establecer($negocio->id);

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
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'propietario', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $usuario;
    }

    private function adminBar(Negocio $negocio, ?Sucursal $sucursal = null): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'admin_bar',
            'sucursal_id' => $sucursal?->id,
            'esta_activa' => true,
        ]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $usuario;
    }

    public function test_desactivar_un_admin_bar_desactiva_su_membresia(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);
        $adminBar = $this->adminBar($negocio, $sucursal);

        $this->actingAs($admin);

        $this->put(route('admin-bar.update', $adminBar), [
            'nombre' => $adminBar->nombre,
            'correo' => $adminBar->correo,
            'sucursal_id' => $sucursal->id,
            'esta_activa' => 0,
        ])->assertRedirect(route('admin-bar.index'));

        $this->assertDatabaseHas('membresias_negocio', [
            'usuario_id' => $adminBar->id,
            'rol' => 'admin_bar',
            'esta_activa' => false,
        ]);
        $this->assertDatabaseHas('usuarios', ['id' => $adminBar->id, 'esta_activo' => false]);
    }

    public function test_admin_bar_desactivado_no_puede_acceder_al_backoffice(): void
    {
        $negocio = $this->bar();
        $this->propietario($negocio);
        $adminBar = $this->adminBar($negocio);

        MembresiaNegocio::where('usuario_id', $adminBar->id)->where('negocio_id', $negocio->id)->update(['esta_activa' => false]);

        app(ContextoNegocio::class)->establecer($negocio->id);
        $this->actingAs($adminBar);

        $this->get(route('cajeros.index'))->assertForbidden();
    }

    public function test_un_bar_puede_crear_un_rol_con_el_mismo_slug_que_otro_bar(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $permiso = Permission::create(['nombre' => 'Ver ventas', 'clave' => 'ver.ventas', 'modulo' => 'ventas']);

        $this->actingAs($admin);
        $this->post(route('roles.store'), [
            'nombre' => 'Mesero A',
            'slug' => 'mesero',
            'permisos' => [$permiso->id],
        ])->assertRedirect(route('roles.index'));

        $otroBar = Negocio::create(['nombre' => 'Bar Dos', 'identificador' => 'bar-s-dos-' . str()->random(6), 'esta_activo' => true, 'numero_sucursales_contratadas' => 5]);
        app(ContextoNegocio::class)->establecer($otroBar->id);
        Membresia::create([
            'negocio_id' => $otroBar->id,
            'plan_id' => Plan::first()->id,
            'estado' => 'activa',
            'fecha_inicio' => now(),
            'fecha_vencimiento' => now()->addDays(30),
        ]);
        $adminOtro = $this->propietario($otroBar);

        $this->actingAs($adminOtro);
        $this->post(route('roles.store'), [
            'nombre' => 'Mesero B',
            'slug' => 'mesero',
            'permisos' => [$permiso->id],
        ])->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['slug' => 'mesero', 'negocio_id' => $negocio->id]);
        $this->assertDatabaseHas('roles', ['slug' => 'mesero', 'negocio_id' => $otroBar->id]);
    }

    public function test_no_se_crea_un_rol_con_un_slug_reservado_del_sistema(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $permiso = Permission::create(['nombre' => 'Ver ventas', 'clave' => 'ver.ventas', 'modulo' => 'ventas']);

        $this->actingAs($admin);

        $this->post(route('roles.store'), [
            'nombre' => 'Falso super admin',
            'slug' => 'super_admin',
            'permisos' => [$permiso->id],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('roles', ['slug' => 'super_admin']);
    }

    public function test_no_se_edita_ni_elimina_un_rol_del_sistema(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $permiso = Permission::create(['nombre' => 'Ver ventas', 'clave' => 'ver.ventas', 'modulo' => 'ventas']);
        $rolSistema = Rol::create(['nombre' => 'Cajero', 'slug' => 'cajero', 'es_sistema' => true]);
        $rolSistema->permisos()->sync([$permiso->id]);

        $this->actingAs($admin);

        $this->put(route('roles.update', $rolSistema), [
            'nombre' => 'Otro nombre',
            'permisos' => [$permiso->id],
        ])->assertStatus(422);

        $this->delete(route('roles.destroy', $rolSistema))->assertStatus(422);

        $this->assertDatabaseHas('roles', ['id' => $rolSistema->id, 'nombre' => 'Cajero']);
    }

    public function test_un_cajero_no_puede_cambiar_la_configuracion_del_negocio(): void
    {
        $negocio = $this->bar();
        $this->propietario($negocio);
        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $this->actingAs($cajero);

        $this->post(route('configuracion.negocio.actualizar'), [
            'nombre_negocio' => 'Bar S',
            'cobrar_impuesto' => 1,
        ])->assertForbidden();
    }

    public function test_la_configuracion_se_guarda_con_booleanos_y_queda_en_auditoria(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);

        $this->actingAs($admin);

        $this->post(route('configuracion.negocio.actualizar'), [
            'nombre_negocio' => 'Bar S',
            'cobrar_impuesto' => '1',
            'descuento_activo' => '1',
            'porcentaje_impuesto' => 12.5,
        ])->assertRedirect(route('configuracion.negocio'));

        $config = ConfiguracionNegocio::firstOrFail();
        $this->assertTrue($config->cobrar_impuesto);
        $this->assertTrue($config->descuento_activo);
        $this->assertSame(12.5, (float) $config->porcentaje_impuesto);

        $this->assertDatabaseHas('auditorias', ['modulo' => 'configuracion', 'accion' => 'actualizar']);
    }

    public function test_las_acciones_sobre_cajeros_roles_y_sucursales_quedan_en_auditoria(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);
        $permiso = Permission::create(['nombre' => 'Ver ventas', 'clave' => 'ver.ventas', 'modulo' => 'ventas']);

        $this->actingAs($admin);

        $this->post(route('cajeros.store'), [
            'nombre' => 'Nuevo cajero',
            'correo' => 'cajero-s@bar.com',
            'clave' => 'secreto123',
            'pin' => '1234',
            'sucursal_id' => $sucursal->id,
        ])->assertRedirect(route('cajeros.index'));

        $this->post(route('roles.store'), [
            'nombre' => 'Mesero',
            'slug' => 'mesero',
            'permisos' => [$permiso->id],
        ])->assertRedirect(route('roles.index'));

        $this->post(route('sucursales.store'), [
            'nombre' => 'Norte',
        ])->assertRedirect(route('sucursales.index'));

        $this->assertDatabaseHas('auditorias', ['modulo' => 'cajeros', 'accion' => 'crear']);
        $this->assertDatabaseHas('auditorias', ['modulo' => 'roles', 'accion' => 'crear']);
        $this->assertDatabaseHas('auditorias', ['modulo' => 'sucursales', 'accion' => 'crear']);
    }

    public function test_el_lookup_del_rol_prefiere_el_rol_del_bar_sobre_el_global(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);

        $rolDelBar = Rol::create(['negocio_id' => $negocio->id, 'nombre' => 'Cajero del bar', 'slug' => 'cajero', 'es_sistema' => false]);
        Rol::create(['nombre' => 'Cajero global', 'slug' => 'cajero', 'es_sistema' => true]);

        $this->actingAs($admin);

        $this->post(route('cajeros.store'), [
            'nombre' => 'Cajero nuevo',
            'correo' => 'cajero-rol@bar.com',
            'clave' => 'secreto123',
            'pin' => '1234',
            'sucursal_id' => $sucursal->id,
        ])->assertRedirect(route('cajeros.index'));

        $this->assertDatabaseHas('membresias_negocio', [
            'rol' => 'cajero',
            'rol_id' => $rolDelBar->id,
        ]);
    }

    public function test_la_relacion_de_membresias_del_rol_funciona_como_has_many(): void
    {
        $negocio = $this->bar();
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);
        $rol = Rol::create(['negocio_id' => $negocio->id, 'nombre' => 'Cajero del bar', 'slug' => 'cajero', 'es_sistema' => false]);

        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'rol_id' => $rol->id, 'sucursal_id' => $sucursal->id, 'esta_activa' => true]);

        $this->assertSame(1, $rol->membresias()->count());
        $this->assertSame($cajero->id, $rol->membresias()->first()->usuario_id);
    }

    public function test_el_propietario_sin_rol_asignado_tiene_acceso_total(): void
    {
        $negocio = $this->bar();
        $propietario = $this->propietario($negocio);

        $this->assertTrue($propietario->tienePermiso('reporte.ventas'));
        $this->assertTrue($propietario->tienePermiso('rol.gestionar'));
        $this->assertTrue($propietario->tienePermiso('permiso.inexistente.futuro'));
    }

    public function test_el_seeder_garantiza_permisos_y_roles_de_sistema_con_permisos(): void
    {
        $this->assertGreaterThan(0, Permission::count());

        $rolCajero = Rol::where('slug', 'cajero')->whereNull('negocio_id')->firstOrFail();
        $this->assertTrue($rolCajero->tienePermiso('pos.cobrar'));
        $this->assertFalse($rolCajero->tienePermiso('reporte.ventas'));

        $rolPropietario = Rol::where('slug', 'propietario')->whereNull('negocio_id')->firstOrFail();
        $this->assertTrue($rolPropietario->tienePermiso('reporte.ventas'));
        $this->assertTrue($rolPropietario->tienePermiso('configuracion.negocio'));
    }

    public function test_se_puede_crear_un_cajero_con_un_rol_personalizado_del_bar(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);
        $permiso = Permission::where('clave', 'pos.cobrar')->firstOrFail();
        $rol = Rol::create(['negocio_id' => $negocio->id, 'nombre' => 'Mesero', 'slug' => 'mesero', 'es_sistema' => false]);
        $rol->permisos()->sync([$permiso->id]);

        $this->actingAs($admin);

        $this->post(route('cajeros.store'), [
            'nombre' => 'Cajero rol',
            'correo' => 'cajero-rol@bar.com',
            'clave' => 'secreto123',
            'pin' => '1234',
            'sucursal_id' => $sucursal->id,
            'rol_id' => $rol->id,
        ])->assertRedirect(route('cajeros.index'));

        $this->assertDatabaseHas('membresias_negocio', [
            'rol' => 'cajero',
            'rol_id' => $rol->id,
        ]);
    }

    public function test_no_se_asigna_a_un_cajero_un_rol_de_otro_bar(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);
        $otroBar = Negocio::create(['nombre' => 'Bar Ajeno', 'identificador' => 'bar-ajeno-' . str()->random(6), 'esta_activo' => true]);
        $rolAjeno = Rol::create(['negocio_id' => $otroBar->id, 'nombre' => 'Rol ajeno', 'slug' => 'ajeno', 'es_sistema' => false]);

        $this->actingAs($admin);

        $this->post(route('cajeros.store'), [
            'nombre' => 'Cajero ajeno',
            'correo' => 'cajero-ajeno@bar.com',
            'clave' => 'secreto123',
            'pin' => '1234',
            'sucursal_id' => $sucursal->id,
            'rol_id' => $rolAjeno->id,
        ])->assertSessionHasErrors('rol_id');

        $this->assertDatabaseMissing('membresias_negocio', ['usuario_id' => \App\Models\User::where('correo', 'cajero-ajeno@bar.com')->value('id')]);
    }

    public function test_no_se_asigna_a_un_cajero_un_rol_del_sistema(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);
        $rolSistema = Rol::where('slug', 'cajero')->whereNull('negocio_id')->firstOrFail();

        $this->actingAs($admin);

        $this->post(route('cajeros.store'), [
            'nombre' => 'Cajero sistema',
            'correo' => 'cajero-sistema@bar.com',
            'clave' => 'secreto123',
            'pin' => '1234',
            'sucursal_id' => $sucursal->id,
            'rol_id' => $rolSistema->id,
        ])->assertSessionHasErrors('rol_id');
    }

    public function test_se_puede_cambiar_el_rol_personalizado_de_un_cajero(): void
    {
        $negocio = $this->bar();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);
        $rol = Rol::create(['negocio_id' => $negocio->id, 'nombre' => 'Mesero', 'slug' => 'mesero', 'es_sistema' => false]);

        $cajero = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $cajero->id,
            'rol' => 'cajero',
            'rol_id' => null,
            'sucursal_id' => $sucursal->id,
            'esta_activa' => true,
        ]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $this->actingAs($admin);

        $this->put(route('cajeros.update', $cajero), [
            'nombre' => $cajero->nombre,
            'correo' => $cajero->correo,
            'sucursal_id' => $sucursal->id,
            'rol_id' => $rol->id,
        ])->assertRedirect(route('cajeros.index'));

        $this->assertDatabaseHas('membresias_negocio', [
            'negocio_id' => $negocio->id,
            'usuario_id' => $cajero->id,
            'rol_id' => $rol->id,
        ]);
    }
}