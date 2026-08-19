<?php

namespace Tests\Feature;

use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Permission;
use App\Models\Rol;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private function bar(): Negocio
    {
        $negocio = Negocio::create(['nombre' => 'Bar Test', 'identificador' => 'bar-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);
        return $negocio;
    }

    private function propietario(Negocio $negocio): User
    {
        $user = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $user->id, 'rol' => 'propietario', 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);
        return $user;
    }

    private function adminBar(Negocio $negocio): User
    {
        $user = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $user->id, 'rol' => 'admin_bar', 'esta_activa' => true]);
        return $user;
    }

    public function test_propietario_puede_ver_roles(): void
    {
        $negocio = $this->bar();
        $user = $this->propietario($negocio);
        $this->actingAs($user);

        $this->get(route('roles.index'))->assertOk()->assertSee('Roles');
    }

    public function test_admin_bar_no_puede_ver_roles(): void
    {
        $negocio = $this->bar();
        $user = $this->adminBar($negocio);
        $this->actingAs($user);

        $this->get(route('roles.index'))->assertStatus(403);
    }

    public function test_propietario_puede_crear_rol(): void
    {
        $negocio = $this->bar();
        $user = $this->propietario($negocio);
        $this->actingAs($user);

        $perm = Permission::firstOrCreate(['clave' => 'test.perm'], ['nombre' => 'Test Perm', 'modulo' => 'Test']);

        $this->post(route('roles.store'), [
            'nombre' => 'Cajero Especial',
            'slug' => 'cajero_especial',
            'descripcion' => 'Cajero con permisos extra',
            'permisos' => [$perm->id],
        ])->assertRedirect();

        $rol = Rol::where('slug', 'cajero_especial')->where('negocio_id', $negocio->id)->first();
        $this->assertNotNull($rol);
        $this->assertFalse($rol->es_sistema);
        $this->assertTrue($rol->permisos->contains('clave', 'test.perm'));
    }

    public function test_no_puede_eliminar_rol_del_sistema(): void
    {
        $negocio = $this->bar();
        $user = $this->propietario($negocio);
        $this->actingAs($user);

        $rol = Rol::where('slug', 'cajero')->whereNull('negocio_id')->firstOrFail();

        $this->delete("/roles/{$rol->id}")->assertStatus(422);
        $this->assertDatabaseHas('roles', ['id' => $rol->id]);
    }

    public function test_puede_eliminar_rol_personalizado(): void
    {
        $negocio = $this->bar();
        $user = $this->propietario($negocio);
        $this->actingAs($user);

        $rol = Rol::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Temporal',
            'slug' => 'temporal',
            'es_sistema' => false,
        ]);

        $this->delete("/roles/{$rol->id}")->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    public function test_rol_personalizado_puede_asignarse_a_membresia(): void
    {
        $negocio = $this->bar();
        $user = User::factory()->create();

        $rol = Rol::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Cajero VIP',
            'slug' => 'cajero_vip',
            'es_sistema' => false,
        ]);

        $perm = Permission::firstOrCreate(['clave' => 'pos.ver'], ['nombre' => 'POS Ver', 'modulo' => 'POS']);
        $rol->permisos()->attach($perm);

        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $user->id,
            'rol' => 'cajero',
            'rol_id' => $rol->id,
            'esta_activa' => true,
        ]);

        $this->actingAs($user);
        $this->assertTrue($user->tienePermiso('pos.ver'));
        $this->assertFalse($user->tienePermiso('producto.eliminar'));
    }

    public function test_propietario_con_rol_id_tiene_todos_los_permisos(): void
    {
        $negocio = $this->bar();
        $user = User::factory()->create();

        $rol = Rol::create([
            'negocio_id' => null,
            'nombre' => 'Propietario VIP',
            'slug' => 'propietario_vip',
            'es_sistema' => true,
        ]);

        $p1 = Permission::firstOrCreate(['clave' => 'pos.ver'], ['nombre' => 'POS Ver', 'modulo' => 'POS']);
        $p2 = Permission::firstOrCreate(['clave' => 'configuracion.negocio'], ['nombre' => 'Config', 'modulo' => 'Config']);
        $p3 = Permission::firstOrCreate(['clave' => 'auditoria.ver'], ['nombre' => 'Aud Ver', 'modulo' => 'Aud']);
        $rol->permisos()->attach([$p1->id, $p2->id, $p3->id]);

        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $user->id,
            'rol' => 'propietario',
            'rol_id' => $rol->id,
            'esta_activa' => true,
        ]);

        $this->actingAs($user);
        $this->assertTrue($user->tienePermiso('pos.ver'));
        $this->assertTrue($user->tienePermiso('configuracion.negocio'));
        $this->assertTrue($user->tienePermiso('auditoria.ver'));
    }
}
