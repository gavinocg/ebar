<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->decimal('fondo_inicial', 10, 2);
            $table->timestamp('abierto_en');
            $table->timestamp('cerrado_en')->nullable();
            $table->decimal('efectivo_esperado', 10, 2)->nullable();
            $table->decimal('efectivo_contado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->string('estado', 20)->default('abierta');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_caja');
    }
};
