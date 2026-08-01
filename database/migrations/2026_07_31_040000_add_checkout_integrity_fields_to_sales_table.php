<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('clave_idempotencia', 100)->nullable()->unique()->after('numero_comprobante');
            $table->boolean('impuesto_habilitado')->default(false)->after('impuesto');
            $table->decimal('porcentaje_impuesto', 5, 2)->default(0)->after('impuesto_habilitado');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['clave_idempotencia']);
            $table->dropColumn(['clave_idempotencia', 'impuesto_habilitado', 'porcentaje_impuesto']);
        });
    }
};
