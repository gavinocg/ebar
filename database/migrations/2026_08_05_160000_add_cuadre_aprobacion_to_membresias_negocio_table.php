<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresias_negocio', function (Blueprint $table) {
            $table->boolean('cuadre_activo')->default(true)->after('rol');
            $table->boolean('aprobacion_activa')->default(true)->after('cuadre_activo');
            $table->unsignedInteger('limite_cajeros')->default(0)->after('aprobacion_activa');
        });
    }

    public function down(): void
    {
        Schema::table('membresias_negocio', function (Blueprint $table) {
            $table->dropColumn(['cuadre_activo', 'aprobacion_activa', 'limite_cajeros']);
        });
    }
};