<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable()->constrained('negocios')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('modulo', 60);
            $table->string('accion', 60);
            $table->string('tipo_referencia', 100)->nullable();
            $table->unsignedBigInteger('id_referencia')->nullable();
            $table->string('descripcion')->nullable();
            $table->json('detalles')->nullable();
            $table->ipAddress('direccion_ip')->nullable();
            $table->timestamps();
            $table->index(['modulo', 'accion']);
            $table->index('id_referencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};