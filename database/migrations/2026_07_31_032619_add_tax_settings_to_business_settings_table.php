<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->boolean('cobrar_impuesto')->default(true)->after('mensaje_comprobante');
            $table->decimal('porcentaje_impuesto', 5, 2)->default(16.00)->after('cobrar_impuesto');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->dropColumn(['cobrar_impuesto', 'porcentaje_impuesto']);
        });
    }
};
