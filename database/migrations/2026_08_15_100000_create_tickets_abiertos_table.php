<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets_abiertos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable();
            $table->foreignId('sucursal_id')->nullable();
            $table->foreignId('turno_caja_id')->nullable();
            $table->foreignId('usuario_id')->nullable();
            $table->string('nombre')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('tickets_abiertos_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable();
            $table->foreignId('ticket_abierto_id')->constrained('tickets_abiertos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable();
            $table->string('nombre_producto');
            $table->integer('cantidad');
            $table->decimal('precio', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets_abiertos_detalles');
        Schema::dropIfExists('tickets_abiertos');
    }
};
