<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('membresias');
        Schema::dropIfExists('planes');
    }

    public function down(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->decimal('precio_mensual', 10, 2)->default(0);
            $table->unsignedInteger('limite_cajeros')->default(1);
            $table->unsignedInteger('limite_cajas')->default(1);
            $table->unsignedInteger('limite_sucursales')->default(1);
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
        });

        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->unique()->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planes')->restrictOnDelete();
            $table->string('estado', 20)->default('prueba');
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->date('fecha_renovacion')->nullable();
            $table->timestamps();
        });
    }
};