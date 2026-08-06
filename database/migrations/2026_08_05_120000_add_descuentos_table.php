<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('descuento', 5, 2)->unsigned()->default(0)->after('precio');
        });

        Schema::table('detalles_venta', function (Blueprint $table) {
            $table->decimal('descuento', 12, 2)->default(0)->after('precio');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('descuento', 12, 2)->default(0)->after('subtotal');
            $table->decimal('descuento_porcentaje', 5, 2)->nullable()->after('descuento');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['descuento', 'descuento_porcentaje']);
        });

        Schema::table('detalles_venta', function (Blueprint $table) {
            $table->dropColumn('descuento');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('descuento');
        });
    }
};