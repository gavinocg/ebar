<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('turno_caja_id')->nullable()->after('clave_idempotencia')->constrained('turnos_caja')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->after('turno_caja_id')->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['turno_caja_id']);
            $table->dropForeign(['usuario_id']);
            $table->dropColumn(['turno_caja_id', 'usuario_id']);
        });
    }
};
