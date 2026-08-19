<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable()->constrained('negocios')->nullOnDelete();
            $table->string('nombre', 50);
            $table->string('slug', 50);
            $table->text('descripcion')->nullable();
            $table->boolean('es_sistema')->default(false);
            $table->timestamps();

            $table->unique(['negocio_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
