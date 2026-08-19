<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresias_negocio', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('rol')->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membresias_negocio', function (Blueprint $table) {
            $table->dropForeign(['rol_id']);
            $table->dropColumn('rol_id');
        });
    }
};
