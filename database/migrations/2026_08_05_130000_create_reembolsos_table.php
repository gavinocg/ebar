<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->enum('tipo', ['parcial', 'total']);
            $table->decimal('monto', 12, 2);
            $table->string('motivo', 500);
            $table->string('metodo', 20)->default('efectivo');
            $table->foreignId('autorizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reembolsos_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reembolso_id')->constrained('reembolsos')->cascadeOnDelete();
            $table->foreignId('detalle_venta_id')->constrained('detalles_venta')->cascadeOnDelete();
            $table->integer('cantidad');
            $table->decimal('monto', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reembolsos_detalles');
        Schema::dropIfExists('reembolsos');
    }
};