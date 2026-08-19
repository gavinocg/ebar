<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->index(['negocio_id', 'usuario_id', 'estado'], 'turnos_caja_negocio_usuario_estado_index');
        });
    }

    public function down(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->dropIndex('turnos_caja_negocio_usuario_estado_index');
        });
    }
};