<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->json('billetes')->nullable()->after('diferencia');
            $table->json('monedas')->nullable()->after('billetes');
        });
    }

    public function down(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->dropColumn(['billetes', 'monedas']);
        });
    }
};