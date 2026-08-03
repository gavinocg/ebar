<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('identificador')->unique();
            $table->boolean('esta_activo')->default(true);
            $table->string('zona_horaria')->default('America/Guayaquil');
            $table->string('moneda', 3)->default('USD');
            $table->timestamps();
        });

        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('esta_activa')->default(true);
            $table->timestamps();
        });

        Schema::create('membresias_negocio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('rol', 30)->default('cajero');
            $table->boolean('esta_activa')->default(true);
            $table->timestamps();
            $table->unique(['negocio_id', 'usuario_id']);
        });

        $negocioId = DB::table('negocios')->insertGetId([
            'nombre' => 'Negocio principal',
            'identificador' => 'negocio-principal',
            'esta_activo' => true,
            'zona_horaria' => 'America/Guayaquil',
            'moneda' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sucursales')->insert([
            'negocio_id' => $negocioId,
            'nombre' => 'Sucursal principal',
            'esta_activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tablas = [
            'categorias', 'productos', 'ventas', 'detalles_venta', 'impresoras',
            'configuraciones_negocio', 'movimientos_inventario', 'cajas',
            'turnos_caja', 'movimientos_efectivo', 'clientes',
        ];

        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('negocio_id')->nullable()->after('id')->constrained('negocios')->nullOnDelete();
                $table->index('negocio_id');
            });
            DB::table($tabla)->update(['negocio_id' => $negocioId]);
        }

        DB::table('usuarios')->get()->each(function ($usuario) use ($negocioId): void {
            DB::table('membresias_negocio')->insert([
                'negocio_id' => $negocioId,
                'usuario_id' => $usuario->id,
                'rol' => $usuario->rol ?: 'cajero',
                'esta_activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        foreach ([
            'clientes', 'movimientos_efectivo', 'turnos_caja', 'cajas',
            'movimientos_inventario', 'configuraciones_negocio', 'impresoras',
            'detalles_venta', 'ventas', 'productos', 'categorias',
        ] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['negocio_id']);
                $table->dropColumn('negocio_id');
            });
        }

        Schema::dropIfExists('membresias_negocio');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('negocios');
    }
};
