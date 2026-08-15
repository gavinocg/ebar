<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        $tables = [
            'productos' => ['precio'],
            'ventas' => ['subtotal', 'impuesto', 'total', 'pagado', 'cambio'],
            'detalles_venta' => ['precio', 'subtotal'],
            'turnos_caja' => ['fondo_inicial', 'efectivo_esperado', 'efectivo_contado', 'diferencia'],
            'movimientos_efectivo' => ['monto'],
            'planes' => ['precio_mensual'],
        ];

        foreach ($tables as $table => $columns) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $driver) {
                foreach ($columns as $column) {
                    if ($driver === 'mysql') {
                        $tableBlueprint->decimal($column, 12, 2)->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        $tables = [
            'productos' => ['precio'],
            'ventas' => ['subtotal', 'impuesto', 'total', 'pagado', 'cambio'],
            'detalles_venta' => ['precio', 'subtotal'],
            'turnos_caja' => ['fondo_inicial', 'efectivo_esperado', 'efectivo_contado', 'diferencia'],
            'movimientos_efectivo' => ['monto'],
            'planes' => ['precio_mensual'],
        ];

        foreach ($tables as $table => $columns) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $driver) {
                foreach ($columns as $column) {
                    if ($driver === 'mysql') {
                        $tableBlueprint->decimal($column, 10, 2)->change();
                    }
                }
            });
        }
    }
};
