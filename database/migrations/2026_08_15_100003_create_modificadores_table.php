<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Groups of modifiers (e.g. "Extras", "Toppings")
        Schema::create('grupos_modificadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable();
            $table->string('nombre'); // e.g. "Extras", "Toppings"
            $table->boolean('requerido')->default(false); // if true, must select at least one
            $table->integer('min_seleccion')->default(0);
            $table->integer('max_seleccion')->nullable(); // null = unlimited
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
        });

        // Individual modifiers (e.g. "Extra cheese", "Double shot")
        Schema::create('modificadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable();
            $table->foreignId('grupo_modificador_id')->constrained('grupos_modificadores')->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('precio_extra', 12, 2)->default(0);
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
        });

        // Pivot: which products have which modifier groups
        Schema::create('producto_grupo_modificador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grupo_modificador_id')->constrained('grupos_modificadores')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['producto_id', 'grupo_modificador_id'], 'prod_grupo_mod_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_grupo_modificador');
        Schema::dropIfExists('modificadores');
        Schema::dropIfExists('grupos_modificadores');
    }
};
