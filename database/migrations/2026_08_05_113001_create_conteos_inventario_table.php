<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conteos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable()->constrained('negocios')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('numero', 30);
            $table->date('fecha')->default(now());
            $table->string('estado', 20)->default('borrador');
            $table->string('notas')->nullable();
            $table->timestamp('aplicado_en')->nullable();
            $table->timestamps();
            $table->index('negocio_id');
        });

        Schema::create('detalles_conteo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteo_inventario_id')->constrained('conteos_inventario')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->integer('existencias_sistema')->unsigned()->default(0);
            $table->integer('existencias_reales')->unsigned()->default(0);
            $table->integer('diferencia')->default(0);
            $table->timestamps();
            $table->index('conteo_inventario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_conteo');
        Schema::dropIfExists('conteos_inventario');
    }
};