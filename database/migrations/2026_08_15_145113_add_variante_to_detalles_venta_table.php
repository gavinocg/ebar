<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_venta', function (Blueprint $table) {
            $table->foreignId('producto_variante_id')->nullable()->after('producto_id');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_venta', function (Blueprint $table) {
            $table->dropForeign(['producto_variante_id']);
            $table->dropColumn('producto_variante_id');
        });
    }
};
