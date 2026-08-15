<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            Schema::table('tickets_abiertos', function (Blueprint $table) {
                $table->foreign('turno_caja_id')->references('id')->on('turnos_caja')->nullOnDelete();
                $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            });

            Schema::table('tickets_abiertos_detalles', function (Blueprint $table) {
                $table->foreign('producto_id')->references('id')->on('productos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            Schema::table('tickets_abiertos', function (Blueprint $table) {
                $table->dropForeign(['turno_caja_id']);
                $table->dropForeign(['usuario_id']);
            });

            Schema::table('tickets_abiertos_detalles', function (Blueprint $table) {
                $table->dropForeign(['producto_id']);
            });
        }
    }
};
