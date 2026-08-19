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
        Schema::table('negocios', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->unique()->after('id');
            $table->string('ruc', 13)->nullable()->unique()->after('identificador');
            $table->string('logo')->nullable()->after('ruc');
            $table->unsignedInteger('numero_sucursales_contratadas')->default(1)->after('moneda');
        });

        DB::table('negocios')->orderBy('id')->each(function ($negocio): void {
            DB::table('negocios')->where('id', $negocio->id)->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropUnique(['ruc']);
            $table->dropColumn(['uuid', 'ruc', 'logo', 'numero_sucursales_contratadas']);
        });
    }
};