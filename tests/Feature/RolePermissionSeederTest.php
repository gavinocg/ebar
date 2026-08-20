<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Rol;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    private function rolGlobal(string $slug): Rol
    {
        return Rol::where('slug', $slug)->whereNull('negocio_id')->firstOrFail();
    }

    public function test_el_rol_cajero_tiene_permisos_de_reembolso(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rol = $this->rolGlobal('cajero');

        $this->assertTrue($rol->tienePermiso('reembolso.ver'));
        $this->assertTrue($rol->tienePermiso('reembolso.crear'));
        $this->assertTrue($rol->tienePermiso('pos.ver'));
        $this->assertFalse($rol->tienePermiso('caja.administrar'));
        $this->assertFalse($rol->tienePermiso('impresora.ver'));
    }

    public function test_el_rol_admin_bar_gestiona_cajeros_sin_manejar_cajas(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rol = $this->rolGlobal('admin_bar');

        $this->assertTrue($rol->tienePermiso('cajero.actualizar'));
        $this->assertTrue($rol->tienePermiso('usuario.cajeros'));
        $this->assertTrue($rol->tienePermiso('reporte.cajeros'));
        $this->assertTrue($rol->tienePermiso('caja.reporte'));
        $this->assertFalse($rol->tienePermiso('caja.administrar'));
        $this->assertFalse($rol->tienePermiso('impresora.ver'));
        $this->assertFalse($rol->tienePermiso('sucursal.ver'));
    }

    public function test_el_rol_propietario_tiene_acceso_completo(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $rol = $this->rolGlobal('propietario');

        $this->assertTrue($rol->tienePermiso('configuracion.negocio'));
        $this->assertTrue($rol->tienePermiso('usuario.admin_bar'));
        $this->assertTrue($rol->tienePermiso('usuario.cajeros'));
        $this->assertTrue($rol->tienePermiso('caja.reporte'));
        $this->assertFalse($rol->tienePermiso('caja.administrar'));
        $this->assertTrue($rol->tienePermiso('impresora.ver'));
        $this->assertTrue($rol->tienePermiso('auditoria.ver'));
        $this->assertTrue($rol->tienePermiso('rol.gestionar'));

        $totalPermisos = Permission::count();
        $this->assertSame($totalPermisos, $rol->permisos()->count());
    }

    public function test_todos_los_permisos_son_asignables_a_alguna_rol(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $asignados = collect(Rol::whereNull('negocio_id')->get())
            ->flatMap(fn (Rol $rol) => $rol->permisos()->pluck('clave'))
            ->unique();

        $huérfanos = Permission::pluck('clave')->diff($asignados);

        $this->assertEmpty($huérfanos, 'Permisos no asignados a ningún rol: ' . $huérfanos->implode(', '));
    }
}
