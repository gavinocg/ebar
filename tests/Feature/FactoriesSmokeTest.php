<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Caja;
use App\Models\Categoria as Category;
use App\Models\ConfiguracionNegocio;
use App\Models\ConteoInventario;
use App\Models\Impresora;
use App\Models\MovimientoInventario;
use App\Models\Negocio;
use App\Models\Producto as Product;
use App\Models\Reembolso;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Models\Venta;
use App\Services\ContextoNegocio;
use Database\Factories\AuditoriaFactory;
use Database\Factories\ConfiguracionNegocioFactory;
use Database\Factories\ConteoInventarioFactory;
use Database\Factories\ImpresoraFactory;
use Database\Factories\MovimientoInventarioFactory;
use Database\Factories\ReembolsoFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_crean_registros_validos(): void
    {
        $negocio = Negocio::create([
            'nombre' => 'Negocio de pruebas',
            'identificador' => 'negocio-factories',
            'esta_activo' => true,
        ]);
        app(ContextoNegocio::class)->establecer($negocio->id);

        $usuario = User::factory()->create();
        $categoria = Category::create(['nombre' => 'Categoría factory']);
        $producto = Product::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto factory',
            'precio' => 5,
            'existencias' => 10,
            'esta_activo' => true,
        ]);
        $caja = Caja::create(['nombre' => 'Caja factory', 'esta_activa' => true]);
        $turno = TurnoCaja::create([
            'caja_id' => $caja->id,
            'usuario_id' => $usuario->id,
            'fondo_inicial' => 50,
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);
        $venta = Venta::create([
            'numero_comprobante' => 'CMP-000001',
            'turno_caja_id' => $turno->id,
            'subtotal' => 5,
            'total' => 5,
            'metodo_pago' => 'efectivo',
            'pagado' => 5,
        ]);

        $movimiento = MovimientoInventarioFactory::new()->create([
            'producto_id' => $producto->id,
            'usuario_id' => $usuario->id,
        ]);
        $this->assertInstanceOf(MovimientoInventario::class, $movimiento);
        $this->assertDatabaseHas('movimientos_inventario', ['id' => $movimiento->id, 'producto_id' => $producto->id]);

        $reembolso = ReembolsoFactory::new()->create([
            'venta_id' => $venta->id,
            'usuario_id' => $usuario->id,
        ]);
        $this->assertInstanceOf(Reembolso::class, $reembolso);
        $this->assertDatabaseHas('reembolsos', ['id' => $reembolso->id, 'venta_id' => $venta->id]);

        $conteo = ConteoInventarioFactory::new()->create(['usuario_id' => $usuario->id]);
        $this->assertInstanceOf(ConteoInventario::class, $conteo);
        $this->assertDatabaseHas('conteos_inventario', ['id' => $conteo->id, 'estado' => 'borrador']);

        $impresora = ImpresoraFactory::new()->create();
        $this->assertInstanceOf(Impresora::class, $impresora);
        $this->assertDatabaseHas('impresoras', ['id' => $impresora->id, 'tipo_conexion' => 'bluetooth']);

        $config = ConfiguracionNegocioFactory::new()->create();
        $this->assertInstanceOf(ConfiguracionNegocio::class, $config);
        $this->assertDatabaseHas('configuraciones_negocio', ['id' => $config->id, 'cobrar_impuesto' => 0]);

        $auditoria = AuditoriaFactory::new()->create(['usuario_id' => $usuario->id]);
        $this->assertInstanceOf(Auditoria::class, $auditoria);
        $this->assertDatabaseHas('auditorias', ['id' => $auditoria->id, 'modulo' => $auditoria->modulo]);
    }
}
