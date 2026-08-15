<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable();
            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('precio', 12, 2);
            $table->string('sku')->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->integer('stock')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_variantes');
    }
};
