<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('productos')
            ->leftJoin(
                DB::raw('(SELECT MIN(id) AS keep_id FROM productos WHERE codigo_barras IS NOT NULL GROUP BY negocio_id, codigo_barras) AS k'),
                'k.keep_id',
                '=',
                'productos.id',
            )
            ->whereNotNull('productos.codigo_barras')
            ->whereNull('k.keep_id')
            ->update(['productos.codigo_barras' => null]);

        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique('productos_codigo_barras_unique');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->unique(['negocio_id', 'codigo_barras'], 'productos_negocio_id_codigo_barras_unique');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique('productos_negocio_id_codigo_barras_unique');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->unique(['codigo_barras'], 'productos_codigo_barras_unique');
        });
    }
};