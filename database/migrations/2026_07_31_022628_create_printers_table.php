<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impresoras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo_conexion', ['bluetooth', 'wifi', 'lan', 'a5']);
            $table->string('direccion')->nullable();
            $table->integer('puerto')->default(9100);
            $table->string('ancho_papel')->default('80mm');
            $table->boolean('esta_activa')->default(true);
            $table->boolean('es_predeterminada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impresoras');
    }
};
