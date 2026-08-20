<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::table('turnos_caja', function (Blueprint $table) use ($driver) {
            $table->dropForeign(['caja_id']);
            $table->dropIndex(['caja_id']);
            $table->dropColumn('caja_id');
        });

        Schema::table('movimientos_efectivo', function (Blueprint $table) use ($driver) {
            $table->dropForeign(['caja_id']);
            $table->dropColumn('caja_id');
        });

        Schema::dropIfExists('cajas');

        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('numero_sucursales_contratadas');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropColumn('n_cajeros_contratados');
        });

        Schema::table('membresias_negocio', function (Blueprint $table) {
            $table->dropColumn('limite_cajeros');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('membresias_negocio', function (Blueprint $table) {
            $table->unsignedInteger('limite_cajeros')->default(0)->after('aprobacion_activa');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->unsignedInteger('n_cajeros_contratados')->default(1)->after('ciudad');
        });

        Schema::table('negocios', function (Blueprint $table) {
            $table->unsignedInteger('numero_sucursales_contratadas')->default(1)->after('moneda');
        });

        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable()->after('id')->constrained('negocios')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->after('negocio_id')->constrained('sucursales')->nullOnDelete();
            $table->string('nombre');
            $table->boolean('esta_activa')->default(true);
            $table->timestamps();
        });

        Schema::table('movimientos_efectivo', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreignId('caja_id')->nullable()->after('turno_caja_id')->constrained('cajas')->restrictOnDelete();
            }
        });

        Schema::table('turnos_caja', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreignId('caja_id')->nullable()->after('negocio_id')->constrained('cajas')->restrictOnDelete();
            }
        });
    }
};