<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->boolean('descuento_activo')->default(false)->after('cobrar_impuesto');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->dropColumn('descuento_activo');
        });
    }
};