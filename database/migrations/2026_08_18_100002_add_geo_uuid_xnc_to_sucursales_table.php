<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->after('id');
            $table->string('provincia')->nullable()->after('direccion');
            $table->string('canton')->nullable()->after('provincia');
            $table->string('ciudad')->nullable()->after('canton');
            $table->unsignedInteger('n_cajeros_contratados')->default(1)->after('telefono');
        });

        DB::table('sucursales')->orderBy('id')->each(function ($sucursal): void {
            DB::table('sucursales')->where('id', $sucursal->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn(['uuid', 'provincia', 'canton', 'ciudad', 'n_cajeros_contratados']);
        });
    }
};