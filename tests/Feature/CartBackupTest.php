<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\User;
use App\Models\MembresiaNegocio;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartBackupTest extends TestCase
{
    use RefreshDatabase;

    private function setupCajero(): User
    {
        $negocio = Negocio::create(['nombre' => 'Bar', 'identificador' => 'bar-cart-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $cajero = User::factory()->create();
        MembresiaNegocio::create(['negocio_id' => $negocio->id, 'usuario_id' => $cajero->id, 'rol' => 'cajero', 'esta_activa' => true]);

        return $cajero;
    }

    public function test_guardar_y_cargar_carrito(): void
    {
        $cajero = $this->setupCajero();
        $this->actingAs($cajero);
        session(['pos_desbloqueado' => true]);

        $carrito = [
            ['id' => '1', 'name' => 'Cerveza', 'price' => 3.50, 'qty' => 2, 'stock' => 50],
            ['id' => '2', 'name' => 'Papas', 'price' => 2.00, 'qty' => 1, 'stock' => 30],
        ];

        $this->postJson(route('punto_venta.guardar_carrito'), ['carrito' => $carrito])
            ->assertOk()->assertJsonPath('success', true);

        $this->getJson(route('punto_venta.cargar_carrito'))
            ->assertOk()->assertJsonCount(2, 'carrito');
    }

    public function test_cargar_carrito_vacio(): void
    {
        $cajero = $this->setupCajero();
        $this->actingAs($cajero);

        $this->getJson(route('punto_venta.cargar_carrito'))
            ->assertOk()->assertJsonPath('carrito', []);
    }

    public function test_pin_lockout_persiste_en_db(): void
    {
        $cajero = $this->setupCajero();
        $cajero->pin = '1234';
        $cajero->save();

        session(['cajero_pin_id' => $cajero->id]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('inicio_sesion.pin'), ['pin' => '0000']);
        }

        $this->assertDatabaseHas('pin_intentos', [
            'usuario_id' => $cajero->id,
            'intentos' => 5,
        ]);

        $intento = \App\Models\IntentoPin::where('usuario_id', $cajero->id)->first();
        $this->assertTrue($intento->estaBloqueado());

        session(['cajero_pin_id' => $cajero->id]);
        $response = $this->post(route('inicio_sesion.pin'), ['pin' => '1234']);
        $response->assertSessionHasErrors('pin');
    }

    public function test_pin_reset_al_acceder_correctamente(): void
    {
        $cajero = $this->setupCajero();
        $cajero->pin = '1234';
        $cajero->save();

        session(['cajero_pin_id' => $cajero->id]);

        $this->post(route('inicio_sesion.pin'), ['pin' => '0000']);
        $this->post(route('inicio_sesion.pin'), ['pin' => '0000']);

        $this->assertDatabaseHas('pin_intentos', ['usuario_id' => $cajero->id, 'intentos' => 2]);

        session(['cajero_pin_id' => $cajero->id]);
        $this->post(route('inicio_sesion.pin'), ['pin' => '1234']);

        $this->assertDatabaseHas('pin_intentos', ['usuario_id' => $cajero->id, 'intentos' => 0, 'bloqueado_hasta' => null]);
    }
}
