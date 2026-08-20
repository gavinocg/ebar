<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table): void {
            $table->dropUnique('negocios_ruc_unique');
        });

        Schema::table('negocios', function (Blueprint $table): void {
            $table->unique(['ruc', 'deleted_at'], 'negocios_ruc_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table): void {
            $table->dropUnique('negocios_ruc_deleted_at_unique');
        });

        Schema::table('negocios', function (Blueprint $table): void {
            $table->unique(['ruc'], 'negocios_ruc_unique');
        });
    }
};