<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Pago;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportesTenancyTest extends TestCase
{
    use RefreshDatabase;

    private function bar(): Negocio
    {
        $plan = Plan::create(['nombre' => 'Básico', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 5, 'limite_sucursales' => 5]);
        $negocio = Negocio::create(['nombre' => 'Bar T', 'identificador' => 'bar-t-' . str()->random(6), 'esta_activo' => true, 'numero_sucursales_contratadas' => 5]);
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

    private function usuarioConRol(Negocio $negocio, Rol $rol, string $slugMembresia = 'cajero'): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => $slugMembresia,
            'rol_id' => $rol->id,
            'esta_activa' => true,
        ]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        return $usuario;
    }

    public function test_un_rol_sin_permisos_de_reportes_no_ve_los_reportes(): void
    {
        $negocio = $this->bar();
        $rol = Rol::create(['negocio_id' => $negocio->id, 'nombre' => 'Mesero', 'slug' => 'mesero', 'es_sistema' => false]);

        $this->actingAs($this->usuarioConRol($negocio, $rol));

        $this->get(route('reportes.productos'))->assertForbidden();
        $this->get(route('reportes.sucursal'))->assertForbidden();
    }

    public function test_rol_con_solo_reporte_cajeros_ve_el_reporte_por_sucursal_pero_no_los_de_ventas(): void
    {
        $negocio = $this->bar();
        $permiso = Permission::where('clave', 'reporte.cajeros')->firstOrFail();
        $rol = Rol::create(['negocio_id' => $negocio->id, 'nombre' => 'Supervisor', 'slug' => 'supervisor', 'es_sistema' => false]);
        $rol->permisos()->sync([$permiso->id]);

        $this->actingAs($this->usuarioConRol($negocio, $rol, 'propietario'));

        $this->get(route('reportes.sucursal'))->assertOk();
        $this->get(route('reportes.productos'))->assertForbidden();
        $this->get(route('reportes.exportar_ventas'))->assertForbidden();
    }

    public function test_total_pagado_suma_solo_los_pagos_registrados_de_la_relacion_cargada(): void
    {
        $negocio = $this->bar();
        $contrato = Contrato::create([
            'negocio_id' => $negocio->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addYear(),
            'forma_contratacion' => 'mensual',
            'estado' => 'activo',
        ]);

        Pago::create(['negocio_id' => $negocio->id, 'contrato_id' => $contrato->id, 'valor' => 50, 'estado' => 'registrado', 'fecha_pago' => now()]);
        Pago::create(['negocio_id' => $negocio->id, 'contrato_id' => $contrato->id, 'valor' => 25, 'estado' => 'registrado', 'fecha_pago' => now()]);
        Pago::create(['negocio_id' => $negocio->id, 'contrato_id' => $contrato->id, 'valor' => 99, 'estado' => 'anulado', 'fecha_pago' => now()]);

        $contrato->load('pagos');

        $this->assertSame(75.0, $contrato->totalPagado());
    }

    public function test_generar_identificador_resuelve_la_colision_con_sufijo(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);
        $admin = User::factory()->create(['rol' => 'super_admin']);

        $this->actingAs($admin);

        $payload = [
            'nombre' => 'Bar Duplicado',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'numero_sucursales_contratadas' => 2,
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'dueno-t@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'nombre_sucursal' => 'Central',
            'n_cajeros_sucursal' => 2,
        ];

        $this->post(route('plataforma.negocios.store'), $payload)->assertRedirect();
        $this->post(route('plataforma.negocios.store'), array_merge($payload, ['correo_admin' => 'dueno-t2@bar.com']))->assertRedirect();

        $this->assertDatabaseHas('negocios', ['identificador' => 'bar-duplicado']);
        $this->assertDatabaseHas('negocios', ['identificador' => 'bar-duplicado-1']);
    }

    public function test_el_ruc_es_opcional_al_crear_un_bar(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);
        $admin = User::factory()->create(['rol' => 'super_admin']);

        $this->actingAs($admin);

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar Sin Ruc',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'numero_sucursales_contratadas' => 1,
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'dueno-sin-ruc@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'nombre_sucursal' => 'Central',
            'n_cajeros_sucursal' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('negocios', ['nombre' => 'Bar Sin Ruc', 'ruc' => null]);
    }

    public function test_turnos_caja_tiene_indice_compuesto_por_negocio_usuario_y_estado(): void
    {
        $this->assertTrue(Schema::hasIndex('turnos_caja', 'turnos_caja_negocio_usuario_estado_index'));
    }

    public function test_el_flash_de_credenciales_se_limpia_despues_de_mostrarse(): void
    {
        $plan = Plan::create(['nombre' => 'Pro', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 3, 'limite_sucursales' => 2]);
        $admin = User::factory()->create(['rol' => 'super_admin']);

        $this->actingAs($admin);

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar Credenciales',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'plan_id' => $plan->id,
            'numero_sucursales_contratadas' => 1,
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'dueno-credenciales@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'nombre_sucursal' => 'Central',
            'n_cajeros_sucursal' => 1,
        ])->assertRedirect();

        $negocio = Negocio::where('nombre', 'Bar Credenciales')->firstOrFail();

        $this->get(route('plataforma.negocios.show', $negocio))->assertOk()->assertSee('Clave temporal');

        $this->get(route('plataforma.negocios.show', $negocio))->assertOk()->assertDontSee('Clave temporal');
    }
}