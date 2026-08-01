<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('tipo', 30);
            $table->integer('cantidad');
            $table->integer('existencias_anteriores');
            $table->integer('existencias_posteriores');
            $table->string('tipo_referencia')->nullable();
            $table->unsignedBigInteger('id_referencia')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['tipo_referencia', 'id_referencia']);
            $table->index(['producto_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
