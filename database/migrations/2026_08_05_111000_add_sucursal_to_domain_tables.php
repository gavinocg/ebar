<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cajas', 'turnos_caja', 'impresoras', 'productos', 'ventas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('sucursal_id')
                    ->nullable()
                    ->after('negocio_id')
                    ->constrained('sucursales')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['cajas', 'turnos_caja', 'impresoras', 'productos', 'ventas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('sucursal_id');
            });
        }
    }
};