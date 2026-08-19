<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_renovacion')->nullable();
            $table->string('forma_contratacion', 20)->default('mensual');
            $table->string('estado', 20)->default('activo');
            $table->string('referencia')->nullable();
            $table->timestamps();
            $table->index(['negocio_id', 'estado']);
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->date('fecha_pago');
            $table->string('concepto', 255)->nullable();
            $table->string('forma_pago', 20)->default('efectivo');
            $table->decimal('valor', 10, 2);
            $table->string('estado', 20)->default('registrado');
            $table->string('referencia', 100)->nullable();
            $table->timestamps();
            $table->index(['contrato_id', 'fecha_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('contratos');
    }
};