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

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private function bar(): Negocio
    {
        $negocio = Negocio::create(['nombre' => 'Bar Test', 'identificador' => 'bar-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);
        return $negocio;
    }

    public function test_cajero_no_puede_acceder_a_reportes(): void
    {
        $negocio = $this->bar();
        $user = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $user->id, 'rol' => 'cajero', 'esta_activa' => true]);
        $this->actingAs($user);

        $this->get(route('reportes.ventas'))->assertStatus(403);
    }

    public function test_admin_bar_puede_ver_productos(): void
    {
        $negocio = $this->bar();
        $user = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $user->id, 'rol' => 'admin_bar', 'esta_activa' => true]);
        $this->actingAs($user);

        $this->get(route('productos.index'))->assertOk();
    }

    public function test_admin_bar_no_puede_ver_auditorias(): void
    {
        $negocio = $this->bar();
        $user = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $user->id, 'rol' => 'admin_bar', 'esta_activa' => true]);
        $this->actingAs($user);

        $this->get(route('auditorias.index'))->assertStatus(403);
    }

    public function test_rol_custom_puede_tener_permisos_especificos(): void
    {
        $negocio = $this->bar();
        $user = User::factory()->create();

        $rol = Rol::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Gerente',
            'slug' => 'gerente',
            'es_sistema' => false,
        ]);

        $verProductos = Permission::firstOrCreate(['clave' => 'producto.ver'], ['nombre' => 'Ver Productos', 'modulo' => 'Productos']);
        $verReportes = Permission::firstOrCreate(['clave' => 'reporte.ventas'], ['nombre' => 'Ver Reportes', 'modulo' => 'Reportes']);

        $rol->permisos()->attach([$verProductos->id, $verReportes->id]);

        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $user->id,
            'rol' => 'admin_bar',
            'rol_id' => $rol->id,
            'esta_activa' => true,
        ]);

        $this->actingAs($user);
        $this->assertTrue($user->tienePermiso('producto.ver'));
        $this->assertTrue($user->tienePermiso('reporte.ventas'));
        $this->assertFalse($user->tienePermiso('producto.eliminar'));
        $this->assertFalse($user->tienePermiso('configuracion.negocio'));
    }

    public function test_aislamiento_entre_negocios(): void
    {
        $bar1 = $this->bar();
        $user = User::factory()->create();

        $rol1 = Rol::create(['negocio_id' => $bar1->id, 'nombre' => 'R1', 'slug' => 'r1', 'es_sistema' => false]);
        $perm = Permission::firstOrCreate(['clave' => 'producto.ver'], ['nombre' => 'Ver Productos', 'modulo' => 'Productos']);
        $rol1->permisos()->attach($perm);

        MembresiaNegocio::create(['negocio_id' => $bar1->id, 'usuario_id' => $user->id, 'rol' => 'admin_bar', 'rol_id' => $rol1->id, 'esta_activa' => true]);

        $bar2 = Negocio::create(['nombre' => 'Bar2', 'identificador' => 'bar2-' . str()->random(6), 'esta_activo' => true]);
        MembresiaNegocio::create(['negocio_id' => $bar2->id, 'usuario_id' => $user->id, 'rol' => 'cajero', 'esta_activa' => true]);

        app(ContextoNegocio::class)->establecer($bar1->id);
        $this->actingAs($user);
        $this->assertTrue($user->tienePermiso('producto.ver'));

        app(ContextoNegocio::class)->establecer($bar2->id);
        $this->assertFalse($user->tienePermiso('producto.ver'));
    }
}
