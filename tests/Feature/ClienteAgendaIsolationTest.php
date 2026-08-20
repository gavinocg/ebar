<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConfiguracionNegocio;
use App\Models\Negocio;
use App\Services\ContextoNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClienteAgendaIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_no_tiene_campos_de_fidelizacion(): void
    {
        $negocio = Negocio::create(['nombre' => 'Test', 'identificador' => 'test-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);
        ConfiguracionNegocio::create(['nombre_negocio' => 'Test', 'cobrar_impuesto' => false, 'porcentaje_impuesto' => 0]);

        $cliente = Cliente::create([
            'nombre' => 'Cliente Test',
            'descripcion' => 'Solo nombre y descripción',
            'esta_activo' => true,
        ]);

        $this->assertFalse(
            Schema::hasColumn('clientes', 'puntos_acumulados'),
            'La tabla clientes no debe tener campos de puntos acumulados'
        );
        $this->assertFalse(
            Schema::hasColumn('clientes', 'puntos_canjeables'),
            'La tabla clientes no debe tener campos de puntos canjeables'
        );
        $this->assertFalse(
            Schema::hasColumn('clientes', 'puntos_gastados'),
            'La tabla clientes no debe tener campos de puntos gastados'
        );
        $this->assertFalse(
            Schema::hasColumn('clientes', 'nivel_fidelidad'),
            'La tabla clientes no debe tener nivel de fidelidad'
        );
        $this->assertFalse(
            Schema::hasColumn('clientes', 'fecha_ultima_compra'),
            'La tabla clientes no debe tener fecha de última compra'
        );

        $fillable = (new Cliente())->getFillable();
        foreach (['puntos_acumulados', 'puntos_canjeables', 'nivel_fidelidad', 'fecha_ultima_compra'] as $campo) {
            $this->assertNotContains($campo, $fillable, "El campo {$campo} no debe ser fillable en Cliente");
        }

        $this->assertSame('Cliente Test', $cliente->nombre);
        $this->assertSame('Solo nombre y descripción', $cliente->descripcion);
        $this->assertTrue($cliente->esta_activo);
    }

    public function test_venta_a_credito_solo_guarda_snapshot_basico_sin_puntos(): void
    {
        $negocio = Negocio::create(['nombre' => 'Test', 'identificador' => 'test-' . str()->random(6), 'esta_activo' => true]);
        app(ContextoNegocio::class)->establecer($negocio->id);
        ConfiguracionNegocio::create(['nombre_negocio' => 'Test', 'cobrar_impuesto' => false, 'porcentaje_impuesto' => 0]);

        $fillable = (new \App\Models\Venta())->getFillable();
        foreach (['puntos_acumulados', 'puntos_gastados', 'nivel_fidelidad', 'descuento_fidelidad'] as $campo) {
            $this->assertNotContains($campo, $fillable, "La venta no debe tener campos de fidelización: {$campo}");
        }
    }
}
