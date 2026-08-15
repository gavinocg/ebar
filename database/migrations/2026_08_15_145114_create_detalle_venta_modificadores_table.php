<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_venta_modificadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_venta_id')->constrained('detalles_venta')->cascadeOnDelete();
            $table->foreignId('modificador_id')->constrained('modificadores')->cascadeOnDelete();
            $table->decimal('precio_extra', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['detalle_venta_id', 'modificador_id'], 'detalle_mod_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_venta_modificadores');
    }
};
