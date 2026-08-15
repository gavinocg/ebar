<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_abiertos_detalles', function (Blueprint $table) {
            $table->foreignId('producto_variante_id')->nullable()->after('producto_id');
            $table->json('modificadores')->nullable()->after('descuento');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_abiertos_detalles', function (Blueprint $table) {
            $table->dropColumn(['producto_variante_id', 'modificadores']);
        });
    }
};
