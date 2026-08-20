<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Sucursal;
use App\Models\TurnoCajero;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajasSucursalesTest extends TestCase
{
    use RefreshDatabase;

    private function barConContrato(int $limiteCajeros = 0, int $limiteSucursales = 0): Negocio
    {
        $negocio = Negocio::create(['nombre' => 'Bar Q', 'identificador' => 'bar-q-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        Contrato::create([
            'negocio_id' => $negocio->id,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDays(30),
            'forma_contratacion' => 'mensual',
            'valor' => 100,
            'numero_sucursales_contratadas' => $limiteSucursales,
            'sucursales_ilimitadas' => $limiteSucursales <= 0,
            'numero_cajeros_contratados' => $limiteCajeros,
            'cajeros_ilimitados' => $limiteCajeros <= 0,
            'estado' => 'activo',
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

    private function cajero(Negocio $negocio, ?Sucursal $sucursal = null): User
    {
        $usuario = User::factory()->create();
        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $usuario->id,
            'rol' => 'cajero',
            'sucursal_id' => $sucursal?->id,
            'esta_activa' => true,
        ]);

        return $usuario;
    }

    public function test_no_se_elimina_una_sucursal_con_historial_de_turnos_y_se_pregunta_antes_de_desactivar(): void
    {
        $negocio = $this->barConContrato();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Histórica', 'esta_activa' => true]);

        TurnoCajero::create([
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'estado' => 'cerrada',
        ]);

        $this->actingAs($admin);

        $this->delete(route('sucursales.destroy', $sucursal))
            ->assertSessionHas('no_eliminable');
        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id]);
        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id, 'esta_activa' => true]);

        $this->post(route('sucursales.desactivar', $sucursal))->assertRedirect(route('sucursales.index'));
        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id, 'esta_activa' => false]);
    }

    public function test_se_elimina_una_sucursal_sin_historial(): void
    {
        $negocio = $this->barConContrato();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Sola', 'esta_activa' => true]);

        $this->actingAs($admin);

        $this->delete(route('sucursales.destroy', $sucursal))->assertRedirect(route('sucursales.index'));
        $this->assertDatabaseMissing('sucursales', ['id' => $sucursal->id]);
    }

    public function test_no_se_mueve_un_cajero_a_otra_sucursal_con_limite_global_lleno(): void
    {
        $negocio = $this->barConContrato(limiteCajeros: 1);
        $admin = $this->propietario($negocio);
        $sucursalA = Sucursal::create(['nombre' => 'A', 'esta_activa' => true]);
        $sucursalB = Sucursal::create(['nombre' => 'B', 'esta_activa' => true]);

        $this->cajero($negocio, $sucursalB);
        $cajeroEnA = $this->cajero($negocio, $sucursalA);

        $this->actingAs($admin);

        $this->put(route('cajeros.update', $cajeroEnA), [
            'nombre' => $cajeroEnA->nombre,
            'correo' => $cajeroEnA->correo,
            'sucursal_id' => $sucursalB->id,
        ])->assertStatus(422);

        $this->assertDatabaseHas('membresias_negocio', ['usuario_id' => $cajeroEnA->id, 'sucursal_id' => $sucursalA->id, 'esta_activa' => true]);
    }

    public function test_se_mueve_un_cajero_a_otra_sucursal_con_cupo_global(): void
    {
        $negocio = $this->barConContrato(limiteCajeros: 5);
        $admin = $this->propietario($negocio);
        $sucursalA = Sucursal::create(['nombre' => 'A', 'esta_activa' => true]);
        $sucursalB = Sucursal::create(['nombre' => 'B', 'esta_activa' => true]);

        $cajeroEnA = $this->cajero($negocio, $sucursalA);

        $this->actingAs($admin);

        $this->put(route('cajeros.update', $cajeroEnA), [
            'nombre' => $cajeroEnA->nombre,
            'correo' => $cajeroEnA->correo,
            'sucursal_id' => $sucursalB->id,
        ])->assertRedirect(route('cajeros.index'));

        $this->assertDatabaseHas('membresias_negocio', ['usuario_id' => $cajeroEnA->id, 'sucursal_id' => $sucursalB->id]);
    }

    public function test_admin_bar_no_puede_asignar_un_cajero_a_otra_sucursal(): void
    {
        $negocio = $this->barConContrato();
        $sucursalA = Sucursal::create(['nombre' => 'A', 'esta_activa' => true]);
        $sucursalB = Sucursal::create(['nombre' => 'B', 'esta_activa' => true]);

        $admin = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $admin->id, 'rol' => 'admin_bar', 'sucursal_id' => $sucursalA->id, 'esta_activa' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $cajeroEnA = $this->cajero($negocio, $sucursalA);

        $this->actingAs($admin);

        $this->put(route('cajeros.update', $cajeroEnA), [
            'nombre' => $cajeroEnA->nombre,
            'correo' => $cajeroEnA->correo,
            'sucursal_id' => $sucursalB->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('membresias_negocio', ['usuario_id' => $cajeroEnA->id, 'sucursal_id' => $sucursalA->id]);
    }

    public function test_no_se_desactiva_un_cajero_con_un_turno_abierto(): void
    {
        $negocio = $this->barConContrato();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);

        TurnoCajero::create([
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        $this->actingAs($admin);

        $this->delete(route('cajeros.destroy', $cajero))->assertStatus(422);
        $this->assertDatabaseHas('membresias_negocio', ['usuario_id' => $cajero->id, 'esta_activa' => true]);
    }
}