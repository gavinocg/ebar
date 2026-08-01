<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('imagen_path')->nullable()->after('descripcion');
            $table->string('icono', 60)->nullable()->after('imagen_path');
            $table->string('color', 7)->default('#334155')->after('icono');
            $table->unsignedInteger('orden')->default(0)->after('color');
            $table->boolean('esta_activa')->default(true)->after('orden');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('imagen_path')->nullable()->after('descripcion');
            $table->string('color', 7)->nullable()->after('imagen_path');
            $table->string('distintivo', 40)->nullable()->after('color');
            $table->string('distintivo_color', 7)->nullable()->after('distintivo');
            $table->boolean('destacado')->default(false)->after('distintivo_color');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['imagen_path', 'color', 'distintivo', 'distintivo_color', 'destacado']);
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn(['imagen_path', 'icono', 'color', 'orden', 'esta_activa']);
        });
    }
};
