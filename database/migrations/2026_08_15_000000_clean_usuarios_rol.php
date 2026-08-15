<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('usuarios')->where('rol', '!=', 'super_admin')->update(['rol' => null]);

        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('rol', 30)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('usuarios')->whereNull('rol')->update(['rol' => 'cajero']);

        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('rol', 30)->default('cajero')->change();
        });
    }
};
