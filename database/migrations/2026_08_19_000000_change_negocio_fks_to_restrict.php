<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLAS = [
        'tickets_abiertos',
        'tickets_abiertos_detalles',
        'producto_variantes',
        'grupos_modificadores',
        'modificadores',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['negocio_id']);
                $table->foreign('negocio_id')->references('id')->on('negocios')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['negocio_id']);
                $table->foreign('negocio_id')->references('id')->on('negocios')->cascadeOnDelete();
            });
        }
    }
};
