<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->enum('tipo_conexion', ['bluetooth', 'wifi', 'lan', 'normal'])->change();
            $table->string('tipo_impresora')->default('termica')->after('tipo_conexion');
        });
    }

    public function down(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->dropColumn('tipo_impresora');
            $table->enum('tipo_conexion', ['bluetooth', 'wifi', 'lan'])->change();
        });
    }
};
