<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::rename('turnos_caja', 'turnos_cajero');

        Schema::table('ventas', function (Blueprint $table) use ($driver) {
            $table->dropForeign(['turno_caja_id']);
            $table->renameColumn('turno_caja_id', 'turno_cajero_id');
            if ($driver === 'mysql') {
                $table->foreign('turno_cajero_id')->references('id')->on('turnos_cajero')->restrictOnDelete();
            }
        });

        Schema::table('tickets_abiertos', function (Blueprint $table) use ($driver) {
            $table->dropForeign(['turno_caja_id']);
            $table->renameColumn('turno_caja_id', 'turno_cajero_id');
            if ($driver === 'mysql') {
                $table->foreign('turno_cajero_id')->references('id')->on('turnos_cajero')->nullOnDelete();
            }
        });

        Schema::table('movimientos_efectivo', function (Blueprint $table) use ($driver) {
            $table->dropForeign(['turno_caja_id']);
            $table->renameColumn('turno_caja_id', 'turno_cajero_id');
            if ($driver === 'mysql') {
                $table->foreign('turno_cajero_id')->references('id')->on('turnos_cajero')->restrictOnDelete();
            }
        });

        Schema::table('turnos_cajero', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->renameIndex('turnos_caja_negocio_usuario_estado_index', 'turnos_cajero_negocio_usuario_estado_index');
            } else {
                $table->dropIndex('turnos_caja_negocio_usuario_estado_index');
                $table->index(['negocio_id', 'usuario_id', 'estado'], 'turnos_cajero_negocio_usuario_estado_index');
            }
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('movimientos_efectivo', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['turno_cajero_id']);
            }
            $table->renameColumn('turno_cajero_id', 'turno_caja_id');
            if ($driver === 'mysql') {
                $table->foreign('turno_caja_id')->references('id')->on('turnos_caja')->restrictOnDelete();
            }
        });

        Schema::table('tickets_abiertos', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['turno_cajero_id']);
            }
            $table->renameColumn('turno_cajero_id', 'turno_caja_id');
            if ($driver === 'mysql') {
                $table->foreign('turno_caja_id')->references('id')->on('turnos_caja')->nullOnDelete();
            }
        });

        Schema::table('ventas', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->dropForeign(['turno_cajero_id']);
            }
            $table->renameColumn('turno_cajero_id', 'turno_caja_id');
            if ($driver === 'mysql') {
                $table->foreign('turno_caja_id')->references('id')->on('turnos_caja')->restrictOnDelete();
            }
        });

        Schema::table('turnos_caja', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->renameIndex('turnos_cajero_negocio_usuario_estado_index', 'turnos_caja_negocio_usuario_estado_index');
            } else {
                $table->dropIndex('turnos_cajero_negocio_usuario_estado_index');
                $table->index(['negocio_id', 'usuario_id', 'estado'], 'turnos_caja_negocio_usuario_estado_index');
            }
        });

        Schema::rename('turnos_cajero', 'turnos_caja');
    }
};