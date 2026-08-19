<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('impresoras')->update([
            'tipo_conexion' => 'bluetooth',
            'ancho_papel' => '58mm',
        ]);

        Schema::table('impresoras', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'puerto', 'tipo_impresora']);
        });
    }

    public function down(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->string('direccion')->nullable();
            $table->integer('puerto')->nullable();
            $table->string('tipo_impresora')->default('termica');
        });
    }
};