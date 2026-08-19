<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Plan;
use App\Models\Sucursal;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajasSucursalesTest extends TestCase
{
    use RefreshDatabase;

    private function barConMembresia(): Negocio
    {
        $plan = Plan::create(['nombre' => 'Básico', 'duracion_dias' => 30, 'limite_cajeros' => 5, 'limite_cajas' => 5, 'limite_sucursales' => 5]);
        $negocio = Negocio::create(['nombre' => 'Bar Q', 'identificador' => 'bar-q-' . str()->random(6), 'esta_activo' => true]);
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

    public function test_no_se_elimina_una_caja_con_historial_de_turnos(): void
    {
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);
        $caja = Caja::create(['nombre' => 'Caja con historial', 'esta_activa' => true]);

        TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'estado' => 'cerrada',
        ]);

        $this->actingAs($admin);

        $this->delete(route('cajas.destroy', $caja))->assertSessionHasErrors('nombre');
        $this->assertDatabaseHas('cajas', ['id' => $caja->id]);
    }

    public function test_se_elimina_una_caja_sin_turnos(): void
    {
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $caja = Caja::create(['nombre' => 'Caja nueva', 'esta_activa' => true]);

        $this->actingAs($admin);

        $this->delete(route('cajas.destroy', $caja))->assertRedirect(route('cajas.index'));
        $this->assertDatabaseMissing('cajas', ['id' => $caja->id]);
    }

    public function test_no_se_elimina_una_sucursal_con_historial_de_turnos(): void
    {
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Histórica', 'esta_activa' => true]);
        $caja = Caja::create(['nombre' => 'Caja', 'sucursal_id' => $sucursal->id, 'esta_activa' => true]);

        TurnoCaja::create([
            'caja_id' => $caja->id,
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $cajero->id,
            'fondo_inicial' => 100,
            'abierto_en' => now()->subHour(),
            'cerrado_en' => now(),
            'estado' => 'cerrada',
        ]);

        $this->actingAs($admin);

        $this->delete(route('sucursales.destroy', $sucursal))->assertSessionHasErrors('nombre');
        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id]);
    }

    public function test_no_se_elimina_una_sucursal_con_cajas_asociadas(): void
    {
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Con cajas', 'esta_activa' => true]);
        Caja::create(['nombre' => 'Caja', 'sucursal_id' => $sucursal->id, 'esta_activa' => true]);

        $this->actingAs($admin);

        $this->delete(route('sucursales.destroy', $sucursal))->assertSessionHasErrors('nombre');
        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id]);
    }

    public function test_se_elimina_una_sucursal_sin_historial(): void
    {
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $sucursal = Sucursal::create(['nombre' => 'Sola', 'esta_activa' => true]);

        $this->actingAs($admin);

        $this->delete(route('sucursales.destroy', $sucursal))->assertRedirect(route('sucursales.index'));
        $this->assertDatabaseMissing('sucursales', ['id' => $sucursal->id]);
    }

    public function test_no_se_mueve_un_cajero_a_una_sucursal_con_limite_lleno(): void
    {
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $sucursalA = Sucursal::create(['nombre' => 'A', 'esta_activa' => true, 'n_cajeros_contratados' => 1]);
        $sucursalB = Sucursal::create(['nombre' => 'B', 'esta_activa' => true, 'n_cajeros_contratados' => 1]);

        $cajeroEnB = $this->cajero($negocio, $sucursalB);
        $cajeroEnA = $this->cajero($negocio, $sucursalA);

        $this->actingAs($admin);

        $this->put(route('cajeros.update', $cajeroEnA), [
            'nombre' => $cajeroEnA->nombre,
            'correo' => $cajeroEnA->correo,
            'sucursal_id' => $sucursalB->id,
        ])->assertStatus(422);

        $this->assertDatabaseHas('membresias_negocio', ['usuario_id' => $cajeroEnA->id, 'sucursal_id' => $sucursalA->id, 'esta_activa' => true]);
    }

    public function test_se_mueve_un_cajero_a_una_sucursal_con_cupo(): void
    {
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $sucursalA = Sucursal::create(['nombre' => 'A', 'esta_activa' => true, 'n_cajeros_contratados' => 1]);
        $sucursalB = Sucursal::create(['nombre' => 'B', 'esta_activa' => true, 'n_cajeros_contratados' => 1]);

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
        $negocio = $this->barConMembresia();
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
        $negocio = $this->barConMembresia();
        $admin = $this->propietario($negocio);
        $cajero = $this->cajero($negocio);
        $caja = Caja::create(['nombre' => 'Caja activa', 'esta_activa' => true]);

        TurnoCaja::create([
            'caja_id' => $caja->id,
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
