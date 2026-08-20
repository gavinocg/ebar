<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->decimal('valor', 12, 2)->default(0)->after('forma_contratacion');
            $table->unsignedInteger('numero_sucursales_contratadas')->default(1)->after('valor');
            $table->boolean('sucursales_ilimitadas')->default(false)->after('numero_sucursales_contratadas');
            $table->unsignedInteger('numero_cajeros_contratados')->default(1)->after('sucursales_ilimitadas');
            $table->boolean('cajeros_ilimitados')->default(false)->after('numero_cajeros_contratados');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('contratos', function (Blueprint $table) {
                $table->string('estado', 20)->default('pendiente')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn([
                'valor',
                'numero_sucursales_contratadas',
                'sucursales_ilimitadas',
                'numero_cajeros_contratados',
                'cajeros_ilimitados',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('contratos', function (Blueprint $table) {
                $table->string('estado', 20)->default('activo')->change();
            });
        }
    }
};
