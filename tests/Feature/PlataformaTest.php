<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Pago;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlataformaTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_contrato_sigue_vigente_el_dia_de_su_vencimiento(): void
    {
        $contrato = new Contrato(['estado' => 'activo', 'fecha_inicio' => now()->subMonth()->toDateString(), 'fecha_fin' => now()->toDateString()]);
        $this->assertTrue($contrato->estaVigente());
        $this->assertFalse($contrato->estaVencido());

        $vencido = new Contrato(['estado' => 'activo', 'fecha_inicio' => now()->subMonths(2)->toDateString(), 'fecha_fin' => now()->subDay()->toDateString()]);
        $this->assertFalse($vencido->estaVigente());
        $this->assertTrue($vencido->estaVencido());
    }

    public function test_aplicar_vencimiento_respeta_suspendidos_y_cancelados(): void
    {
        $suspendido = new Contrato(['estado' => 'suspendido', 'fecha_inicio' => now()->subMonths(2), 'fecha_fin' => now()->subDay()]);
        $suspendido->aplicarVencimiento();
        $this->assertSame('suspendido', $suspendido->estado);

        $cancelado = new Contrato(['estado' => 'cancelado', 'fecha_inicio' => now()->subMonths(2), 'fecha_fin' => now()->subDay()]);
        $cancelado->aplicarVencimiento();
        $this->assertSame('cancelado', $cancelado->estado);
    }

    public function test_un_bar_no_puede_tener_dos_contratos_pendientes_o_activos(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = Negocio::create(['nombre' => 'Bar', 'identificador' => 'bar-' . str()->random(6), 'esta_activo' => true]);

        $payload = [
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addYear()->toDateString(),
            'forma_contratacion' => 'mensual',
            'valor' => 100,
            'numero_sucursales_contratadas' => 1,
            'numero_cajeros_contratados' => 1,
        ];

        $this->post(route('plataforma.negocios.contratos.store', $negocio), $payload)->assertSessionHas('success');
        $this->post(route('plataforma.negocios.contratos.store', $negocio), $payload)->assertStatus(422);

        $this->assertSame(1, \App\Models\Contrato::where('negocio_id', $negocio->id)->whereIn('estado', ['pendiente', 'activo'])->count());
    }

    public function test_eliminar_bar_desactiva_membresias_y_cancela_contratos(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar a eliminar',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'eliminar@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
        ])->assertRedirect();

        $negocio = Negocio::where('identificador', 'bar-a-eliminar')->firstOrFail();
        Contrato::create([
            'negocio_id' => $negocio->id,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDays(30),
            'forma_contratacion' => 'mensual',
            'valor' => 100,
            'numero_sucursales_contratadas' => 1,
            'numero_cajeros_contratados' => 1,
            'estado' => 'activo',
        ]);

        $this->delete(route('plataforma.negocios.destroy', $negocio))->assertRedirect();

        $this->assertSoftDeleted('negocios', ['id' => $negocio->id]);
        $this->assertDatabaseHas('membresias_negocio', ['negocio_id' => $negocio->id, 'esta_activa' => false]);
        $this->assertDatabaseHas('contratos', ['negocio_id' => $negocio->id, 'estado' => 'cancelado']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['rol' => 'super_admin']);
    }

    private function crearBarConContrato(array $atributos = [], array $contrato = []): Negocio
    {
        $negocio = Negocio::create(array_merge([
            'nombre' => 'Bar de prueba',
            'identificador' => 'bar-prueba-' . str()->random(6),
            'esta_activo' => true,
        ], $atributos));

        app(ContextoNegocio::class)->establecer($negocio->id);

        Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);

        Contrato::create(array_merge([
            'negocio_id' => $negocio->id,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDays(30),
            'forma_contratacion' => 'mensual',
            'valor' => 100,
            'numero_sucursales_contratadas' => 1,
            'numero_cajeros_contratados' => 1,
            'estado' => 'activo',
        ], $contrato));

        return $negocio;
    }

    public function test_super_admin_ve_la_lista_de_bares(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('plataforma.negocios.index'))->assertOk()->assertSee('Bares registrados');
    }

    public function test_super_admin_ve_el_detalle_de_un_bar_de_otro_negocio_sin_contexto(): void
    {
        $this->actingAs($this->superAdmin());

        $otroBar = $this->crearBarConContrato(['nombre' => 'Bar contexto activo']);

        $negocio = Negocio::create(['nombre' => 'Bar sin contexto', 'identificador' => 'bar-sin-contexto', 'esta_activo' => true]);
        $sucursal = new Sucursal(['nombre' => 'Principal', 'esta_activa' => true]);
        $sucursal->negocio_id = $negocio->id;
        $sucursal->save();

        $this->assertSame(1, Sucursal::withoutGlobalScope('negocio')->where('negocio_id', $negocio->id)->count());

        $negocio->load(['sucursales' => fn ($q) => $q->withoutGlobalScope('negocio')]);
        $this->assertSame(1, $negocio->sucursales->count());

        $this->get(route('plataforma.negocios.show', $negocio))
            ->assertOk()
            ->assertSee('Bar sin contexto')
            ->assertSee('1 activas');
    }

    public function test_usuario_del_bar_no_puede_gestionar_bares(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('plataforma.negocios.index'))->assertForbidden();
    }

    public function test_super_admin_crea_un_bar_con_admin_inicial(): void
    {
        Mail::fake();

        $this->actingAs($this->superAdmin());

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar San Felipe',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'dueno@bar.com',
            'cedula_admin' => '1002003000',
            'celular_admin' => '0964142527',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'nombre_sucursal' => 'Central',
        ])->assertRedirect(route('plataforma.negocios.index'));

        $negocio = Negocio::where('identificador', 'bar-san-felipe')->firstOrFail();
        $this->assertSame('Bar San Felipe', $negocio->nombre);
        $this->assertDatabaseHas('sucursales', ['negocio_id' => $negocio->id, 'nombre' => 'Central']);
        $this->assertDatabaseHas('usuarios', ['correo' => 'dueno@bar.com']);
        $this->assertDatabaseHas('membresias_negocio', ['negocio_id' => $negocio->id, 'usuario_id' => User::where('correo', 'dueno@bar.com')->first()->id, 'rol' => 'propietario']);
        $this->assertDatabaseHas('configuraciones_negocio', ['negocio_id' => $negocio->id]);
    }

    public function test_creacion_de_bar_genera_identificadores_unicos(): void
    {
        $this->actingAs($this->superAdmin());

        $base = [
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
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
        $this->actingAs($this->superAdmin());

        $base = [
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'nombre_admin' => 'A',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
            'ruc' => '1002003000001',
        ];

        $this->post(route('plataforma.negocios.store'), array_merge($base, ['nombre' => 'Uno', 'correo_admin' => 'a@b.com']))->assertRedirect();
        $this->post(route('plataforma.negocios.store'), array_merge($base, ['nombre' => 'Dos', 'correo_admin' => 'b@c.com']))
            ->assertSessionHasErrors('ruc')
            ->assertSessionHas('errors');

        $errores = session('errors');
        $this->assertStringContainsString('El campo RUC ya ha sido registrado.', $errores->first('ruc'));

        $this->assertSame(1, Negocio::where('ruc', '1002003000001')->count());
    }

    public function test_plataforma_actualiza_un_bar_ignorando_campos_obsoletos(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConContrato();

        $this->get(route('plataforma.negocios.edit', $negocio))
            ->assertOk()
            ->assertSee('Datos Bar')
            ->assertSee('Contratos y pagos');

        $this->put(route('plataforma.negocios.update', $negocio), [
            'nombre' => 'Bar renombrado',
            'numero_sucursales_contratadas' => 1,
            'plan_id' => 999,
        ])->assertRedirect();

        $this->assertSame('Bar renombrado', $negocio->fresh()->nombre);
    }

    public function test_super_admin_actualiza_el_propietario_de_un_bar(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar Propietario',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'nombre_admin' => 'Dueño Viejo',
            'correo_admin' => 'propietario@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
        ])->assertRedirect();

        $negocio = Negocio::where('identificador', 'bar-propietario')->firstOrFail();

        $this->post(route('plataforma.negocios.propietario.update', $negocio), [
            'nombre' => 'Dueño Nuevo',
            'correo' => 'dueno.nuevo@bar.com',
            'cedula' => '1002003000',
            'celular' => '0964142527',
            'esta_activo' => '1',
            'clave' => 'nuevaClave123',
            'clave_confirmation' => 'nuevaClave123',
        ])->assertRedirect();

        $usuario = User::where('correo', 'dueno.nuevo@bar.com')->firstOrFail();
        $this->assertSame('Dueño Nuevo', $usuario->nombre);
        $this->assertSame('1002003000', $usuario->cedula);
        $this->assertSame('0964142527', $usuario->celular);
        $this->assertTrue($usuario->esta_activo);
        $this->assertTrue($usuario->debe_cambiar_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nuevaClave123', $usuario->password));
    }

    public function test_super_admin_gestiona_sucursales_desde_la_plataforma(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('plataforma.negocios.store'), [
            'nombre' => 'Bar Sucursales',
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'nombre_admin' => 'Dueño',
            'correo_admin' => 'sucursales@bar.com',
            'clave_admin' => 'secreto123',
            'clave_admin_confirmation' => 'secreto123',
        ])->assertRedirect();

        $negocio = Negocio::where('identificador', 'bar-sucursales')->firstOrFail();

        $this->post(route('plataforma.negocios.sucursales.store', $negocio), [
            'nombre' => 'Centro',
            'direccion' => 'Av. 10 de Agosto',
            'ciudad' => 'Quito',
        ])->assertRedirect();

        $sucursal = Sucursal::withoutGlobalScope('negocio')
            ->where('negocio_id', $negocio->id)
            ->where('nombre', 'Centro')
            ->firstOrFail();

        $this->put(route('plataforma.sucursales.update', $sucursal->id), [
            'nombre' => 'Centro Norte',
            'ciudad' => 'Quito',
            'esta_activa' => '0',
        ])->assertRedirect();

        $this->assertSame('Centro Norte', $sucursal->fresh()->nombre);
        $this->assertFalse($sucursal->fresh()->esta_activa);

        $this->delete(route('plataforma.sucursales.destroy', $sucursal->id))->assertRedirect();
        $this->assertDatabaseMissing('sucursales', ['id' => $sucursal->id]);
    }

    public function test_bar_con_ventas_no_se_elimina_y_se_pregunta_antes_de_desactivar(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConContrato();

        Venta::withoutGlobalScope('negocio')->create([
            'negocio_id' => $negocio->id,
            'numero_comprobante' => 'V-0001',
            'subtotal' => 10,
            'total' => 10,
            'metodo_pago' => 'efectivo',
            'pagado' => 10,
        ]);

        $this->delete(route('plataforma.negocios.destroy', $negocio))->assertSessionHas('no_eliminable');

        $this->assertNotSoftDeleted('negocios', ['id' => $negocio->id]);
        $this->assertDatabaseHas('negocios', ['id' => $negocio->id, 'esta_activo' => true]);

        $this->post(route('plataforma.negocios.desactivar', $negocio))->assertRedirect();
        $this->assertDatabaseHas('negocios', ['id' => $negocio->id, 'esta_activo' => false]);
    }

    public function test_contrato_con_pagos_no_se_elimina_y_se_pregunta_antes_de_desactivar(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConContrato();
        $contrato = $negocio->contratos()->firstOrFail();

        Pago::create([
            'contrato_id' => $contrato->id,
            'fecha_pago' => now()->toDateString(),
            'concepto' => 'Cuota inicial',
            'forma_pago' => 'efectivo',
            'valor' => 50,
            'estado' => 'registrado',
        ]);

        $this->delete(route('plataforma.contratos.destroy', $contrato))->assertSessionHas('no_eliminable');

        $this->assertDatabaseHas('contratos', ['id' => $contrato->id, 'estado' => 'activo']);

        $this->post(route('plataforma.contratos.desactivar', $contrato))->assertRedirect();
        $this->assertDatabaseHas('contratos', ['id' => $contrato->id, 'estado' => 'suspendido']);
    }

    public function test_cambiar_estado_de_contrato_desde_la_plataforma(): void
    {
        $this->actingAs($this->superAdmin());
        $negocio = $this->crearBarConContrato();
        $contrato = $negocio->contratos()->firstOrFail();

        $this->post(route('plataforma.contratos.estado', $contrato), ['estado' => 'suspendido'])->assertSessionHas('success');
        $this->assertSame('suspendido', $contrato->fresh()->estado);

        $this->post(route('plataforma.contratos.estado', $contrato), ['estado' => 'activo'])->assertSessionHas('success');
        $this->assertSame('activo', $contrato->fresh()->estado);
    }

    public function test_bar_suspendido_bloquea_el_acceso_del_tenant(): void
    {
        $negocio = $this->crearBarConContrato(['esta_activo' => false]);
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('punto_venta.inicio'))->assertForbidden();
    }

    public function test_el_selector_muestra_los_bares_del_usuario(): void
    {
        $negocioUno = $this->crearBarConContrato();
        $negocioDos = $this->crearBarConContrato();
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
        $negocio = $this->crearBarConContrato();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('negocio.seleccionar'))->assertRedirect(route('panel.inicio'));
    }

    public function test_un_cajero_con_un_solo_bar_va_al_pos(): void
    {
        $negocio = $this->crearBarConContrato();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('negocio.seleccionar'))->assertRedirect(route('punto_venta.inicio'));
    }

    public function test_rol_legacy_administrador_va_al_panel(): void
    {
        $negocio = $this->crearBarConContrato();
        $usuario = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $usuario->id, 'rol' => 'administrador', 'esta_activa' => true]);

        $this->actingAs($usuario);

        $this->get(route('punto_venta.inicio'))->assertRedirect(route('panel.inicio'));
        $this->get(route('panel.inicio'))->assertOk();
    }
}