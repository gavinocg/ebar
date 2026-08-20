<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::table('detalles_venta', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('producto_variante_id')->references('id')->on('producto_variantes')->nullOnDelete();
            }
        });

        Schema::table('tickets_abiertos', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('negocio_id')->references('id')->on('negocios')->restrictOnDelete();
                $table->foreign('sucursal_id')->references('id')->on('sucursales')->nullOnDelete();
            }
        });

        Schema::table('tickets_abiertos_detalles', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('negocio_id')->references('id')->on('negocios')->restrictOnDelete();
                $table->foreign('producto_variante_id')->references('id')->on('producto_variantes')->nullOnDelete();
            }
        });

        Schema::table('producto_variantes', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('negocio_id')->references('id')->on('negocios')->restrictOnDelete();
            }
        });

        Schema::table('grupos_modificadores', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('negocio_id')->references('id')->on('negocios')->restrictOnDelete();
            }
        });

        Schema::table('modificadores', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('negocio_id')->references('id')->on('negocios')->restrictOnDelete();
            }
        });

        // Indexes for sales queries
        Schema::table('ventas', function (Blueprint $table) {
            $table->index('turno_caja_id');
            $table->index('usuario_id');
            $table->index('cliente_id');
            $table->index('created_at');
            $table->index('estado_cobro');
        });

        Schema::table('detalles_venta', function (Blueprint $table) {
            $table->index('producto_id');
            $table->index('producto_variante_id');
        });

        Schema::table('reembolsos', function (Blueprint $table) {
            $table->index('venta_id');
            $table->index('negocio_id');
            $table->index('usuario_id');
        });

        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->index('producto_id');
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->index('proveedor_id');
            $table->index('usuario_id');
            $table->index('estado');
        });

        Schema::table('tickets_abiertos', function (Blueprint $table) {
            $table->index('turno_caja_id');
            $table->index('usuario_id');
        });

        Schema::table('auditorias', function (Blueprint $table) {
            $table->index('negocio_id');
            $table->index('usuario_id');
            $table->index('created_at');
        });

        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->index('caja_id');
        });

        // Unique constraints
        Schema::table('categorias', function (Blueprint $table) {
            $table->unique(['negocio_id', 'nombre']);
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->unique(['negocio_id', 'numero']);
        });

        Schema::table('conteos_inventario', function (Blueprint $table) {
            $table->unique(['negocio_id', 'numero']);
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropIndex(['negocio_id', 'nombre']);
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex(['negocio_id', 'numero']);
        });

        Schema::table('conteos_inventario', function (Blueprint $table) {
            $table->dropIndex(['negocio_id', 'numero']);
        });

        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->dropIndex(['caja_id']);
        });

        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropIndex(['negocio_id']);
            $table->dropIndex(['usuario_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('tickets_abiertos', function (Blueprint $table) {
            $table->dropIndex(['turno_caja_id']);
            $table->dropIndex(['usuario_id']);
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex(['proveedor_id']);
            $table->dropIndex(['usuario_id']);
            $table->dropIndex(['estado']);
        });

        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->dropIndex(['producto_id']);
        });

        Schema::table('reembolsos', function (Blueprint $table) {
            $table->dropIndex(['venta_id']);
            $table->dropIndex(['negocio_id']);
            $table->dropIndex(['usuario_id']);
        });

        Schema::table('detalles_venta', function (Blueprint $table) {
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['producto_variante_id']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['turno_caja_id']);
            $table->dropIndex(['usuario_id']);
            $table->dropIndex(['cliente_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['estado_cobro']);
        });

        Schema::table('modificadores', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['negocio_id']);
            }
        });

        Schema::table('grupos_modificadores', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['negocio_id']);
            }
        });

        Schema::table('producto_variantes', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['negocio_id']);
            }
        });

        Schema::table('tickets_abiertos_detalles', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['negocio_id']);
                $table->dropForeign(['producto_variante_id']);
            }
        });

        Schema::table('tickets_abiertos', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['negocio_id']);
                $table->dropForeign(['sucursal_id']);
            }
        });

        Schema::table('detalles_venta', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['producto_variante_id']);
            }
        });
    }
};
