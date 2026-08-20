<?php

namespace Tests\Feature;

use App\Models\IntentoPin;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rechaza_usuario_inactivo(): void
    {
        $usuario = User::factory()->create(['esta_activo' => false]);

        $this->post(route('inicio_sesion.guardar'), [
            'correo' => $usuario->correo,
            'password' => 'password',
        ])->assertSessionHasErrors('correo');

        $this->assertGuest();
    }

    public function test_login_redirige_super_admin_a_la_plataforma(): void
    {
        $usuario = User::factory()->create(['rol' => 'super_admin']);

        $this->post(route('inicio_sesion.guardar'), [
            'correo' => $usuario->correo,
            'password' => 'password',
        ])->assertRedirectToRoute('plataforma.inicio');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_pin_de_usuario_inactivo_redirige_al_login_de_cajero(): void
    {
        $inactivo = User::factory()->create(['esta_activo' => false, 'pin' => Hash::make('1234')]);

        $this->withSession(['cajero_pin_id' => $inactivo->id])
            ->get(route('inicio_sesion.pin'))
            ->assertRedirectToRoute('inicio_sesion.cajero');
    }

    public function test_desbloquear_pos_con_pin_correcto(): void
    {
        $bar = $this->crearBar();
        $cajero = User::factory()->create(['pin' => Hash::make('1234')]);
        MembresiaNegocio::create(['negocio_id' => $bar->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $this->actingAs($cajero)
            ->withSession(['negocio_id' => $bar->id])
            ->post(route('punto_venta.desbloquear'), ['pin' => '1234'])
            ->assertRedirect(route('punto_venta.inicio'));

        $this->assertTrue(session('pos_desbloqueado'));
        $this->assertDatabaseHas('pin_intentos', ['usuario_id' => $cajero->id, 'intentos' => 0]);
    }

    public function test_desbloquear_pos_se_bloquea_despues_de_cinco_intentos(): void
    {
        $bar = $this->crearBar();
        $cajero = User::factory()->create(['pin' => Hash::make('1234')]);
        MembresiaNegocio::create(['negocio_id' => $bar->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        $this->actingAs($cajero)->withSession(['negocio_id' => $bar->id]);

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('punto_venta.desbloquear'), ['pin' => '0000'])->assertSessionHasErrors('pin');
        }

        $this->post(route('punto_venta.desbloquear'), ['pin' => '0000'])
            ->assertSessionHasErrors('pin');

        $this->post(route('punto_venta.desbloquear'), ['pin' => '1234'])
            ->assertSessionHasErrors('pin');

        $this->assertNull(session('pos_desbloqueado'));
    }

    public function test_la_plataforma_exige_cambio_de_password(): void
    {
        $superAdmin = User::factory()->create(['rol' => 'super_admin', 'debe_cambiar_password' => true]);

        $this->actingAs($superAdmin)
            ->get(route('plataforma.inicio'))
            ->assertRedirectToRoute('password.cambiar');
    }

    public function test_super_admin_en_la_raiz_va_a_la_plataforma(): void
    {
        $superAdmin = User::factory()->create(['rol' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get('/')
            ->assertRedirectToRoute('plataforma.inicio');
    }

    public function test_guardar_password_invalida_otras_sesiones_y_remember_tokens(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_password' => true]);

        DB::table('sessions')->insert([
            'id' => 'otra-sesion',
            'user_id' => $usuario->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'navegador',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($usuario)->post(route('password.cambiar.guardar'), [
            'password_actual' => 'password',
            'password' => 'nuevaclave123',
            'password_confirmation' => 'nuevaclave123',
        ])->assertRedirectToRoute('negocio.seleccionar');

        $this->assertDatabaseMissing('sessions', ['id' => 'otra-sesion']);
        $this->assertNull($usuario->refresh()->remember_token);
        $this->assertFalse($usuario->refresh()->debe_cambiar_password);
        $this->assertTrue(Hash::check('nuevaclave123', $usuario->refresh()->password));
    }

    public function test_intento_pin_resetea_el_contador_cuando_el_bloqueo_vencio(): void
    {
        $usuario = User::factory()->create();
        $intento = IntentoPin::create([
            'usuario_id' => $usuario->id,
            'intentos' => 5,
            'bloqueado_hasta' => now()->subMinute(),
        ]);

        $intento->registrarFallo();

        $intento->refresh();
        $this->assertSame(1, $intento->intentos);
        $this->assertNull($intento->bloqueado_hasta);
    }

    public function test_usuario_inactivo_no_accede_al_sistema(): void
    {
        $bar = $this->crearBar();
        $usuario = User::factory()->create(['esta_activo' => false]);
        MembresiaNegocio::create(['negocio_id' => $bar->id, 'usuario_id' => $usuario->id, 'rol' => 'admin_bar', 'esta_activa' => true]);

        $this->actingAs($usuario)
            ->withSession(['negocio_id' => $bar->id])
            ->get(route('panel.inicio'))
            ->assertRedirectToRoute('inicio_sesion');

        $this->assertGuest();
    }

    private function crearBar(): Negocio
    {

        $negocio = Negocio::create([
            'nombre' => 'Bar de prueba',
            'identificador' => 'bar-prueba-' . str()->random(6),
            'esta_activo' => true,
        ]);

        app(ContextoNegocio::class)->establecer($negocio->id);

        Sucursal::create(['nombre' => 'Principal', 'esta_activa' => true]);


        return $negocio;
    }
}
