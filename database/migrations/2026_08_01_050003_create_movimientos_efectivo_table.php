<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_efectivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_caja_id')->constrained('turnos_caja')->restrictOnDelete();
            $table->foreignId('caja_id')->constrained('cajas')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->decimal('monto', 10, 2);
            $table->string('motivo', 255)->nullable();
            $table->string('tipo_referencia')->nullable();
            $table->unsignedBigInteger('id_referencia')->nullable();
            $table->timestamps();

            $table->index(['turno_caja_id', 'created_at']);
            $table->index(['tipo_referencia', 'id_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_efectivo');
    }
};
