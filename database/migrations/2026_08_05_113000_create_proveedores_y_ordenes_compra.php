<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable()->constrained('negocios')->nullOnDelete();
            $table->string('nombre');
            $table->string('ruc')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->string('direccion')->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
            $table->index('negocio_id');
        });

        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable()->constrained('negocios')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('numero', 30);
            $table->date('fecha')->default(now());
            $table->string('estado', 20)->default('pendiente');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('notas')->nullable();
            $table->timestamp('recibida_en')->nullable();
            $table->timestamps();
            $table->index('negocio_id');
        });

        Schema::create('detalles_orden_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->integer('cantidad')->unsigned();
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
            $table->index('orden_compra_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_orden_compra');
        Schema::dropIfExists('ordenes_compra');
        Schema::dropIfExists('proveedores');
    }
};