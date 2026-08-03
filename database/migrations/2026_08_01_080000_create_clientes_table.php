<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();

            $table->index(['esta_activo', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
