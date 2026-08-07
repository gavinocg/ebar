<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->foreignId('aprobado_por')->nullable()->after('monedas')->constrained('usuarios')->nullOnDelete();
            $table->timestamp('aprobado_en')->nullable()->after('aprobado_por');
        });
    }

    public function down(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprobado_por');
            $table->dropColumn('aprobado_en');
        });
    }
};