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
        $negocioId = DB::table('negocios')->pluck('id')->first();

        if ($driver === 'mysql') {
            $hasUnique = DB::select("SHOW INDEX FROM ventas WHERE Key_name = 'ventas_clave_idempotencia_unique'");
            if (empty($hasUnique)) {
                Schema::table('ventas', function (Blueprint $table) {
                    $table->unique('clave_idempotencia');
                });
            }
        } elseif ($driver === 'sqlite') {
            $hasUnique = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='ventas' AND name='ventas_clave_idempotencia_unique'");
            if (empty($hasUnique)) {
                Schema::table('ventas', function (Blueprint $table) {
                    $table->unique('clave_idempotencia');
                });
            }
        }

        foreach (['categorias', 'configuraciones_negocio', 'movimientos_inventario', 'conteos_inventario', 'clientes', 'movimientos_efectivo'] as $tabla) {
            $hasColumn = false;
            if ($driver === 'mysql') {
                $colResult = DB::select("SHOW COLUMNS FROM {$tabla} WHERE Field = 'sucursal_id'");
                $hasColumn = !empty($colResult);
            } else {
                $colResult = DB::select("PRAGMA table_info({$tabla})");
                foreach ($colResult as $col) {
                    if ($col->name === 'sucursal_id') {
                        $hasColumn = true;
                        break;
                    }
                }
            }

            if (!$hasColumn) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->foreignId('sucursal_id')
                        ->nullable()
                        ->after('negocio_id')
                        ->constrained('sucursales')
                        ->nullOnDelete();
                });
            }

            if ($negocioId) {
                $sucursalId = DB::table('sucursales')
                    ->where('negocio_id', $negocioId)
                    ->where('esta_activa', true)
                    ->value('id');
                if ($sucursalId) {
                    DB::table($tabla)
                        ->whereNull('sucursal_id')
                        ->update(['sucursal_id' => $sucursalId]);
                }
            }
        }

        if ($driver === 'mysql') {
            $configNullable = DB::select("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuraciones_negocio' AND COLUMN_NAME = 'negocio_id'");
            if (!empty($configNullable) && $configNullable[0]->IS_NULLABLE === 'YES') {
                if ($negocioId) {
                    DB::table('configuraciones_negocio')
                        ->whereNull('negocio_id')
                        ->update(['negocio_id' => $negocioId]);
                }
                Schema::table('configuraciones_negocio', function (Blueprint $table) {
                    $table->dropForeign(['negocio_id']);
                });
                Schema::table('configuraciones_negocio', function (Blueprint $table) {
                    $table->foreignId('negocio_id')
                        ->nullable(false)
                        ->change();
                });
                Schema::table('configuraciones_negocio', function (Blueprint $table) {
                    $table->foreign('negocio_id')
                        ->references('id')
                        ->on('negocios')
                        ->restrictOnDelete();
                });
            }

            $fkExists = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reembolsos' AND CONSTRAINT_NAME = 'reembolsos_usuario_id_foreign' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            if (!empty($fkExists)) {
                Schema::table('reembolsos', function (Blueprint $table) {
                    $table->dropForeign(['usuario_id']);
                });
            }
            $colNullable = DB::select("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reembolsos' AND COLUMN_NAME = 'usuario_id'");
            if (!empty($colNullable) && $colNullable[0]->IS_NULLABLE === 'NO') {
                Schema::table('reembolsos', function (Blueprint $table) {
                    $table->foreignId('usuario_id')->nullable()->change();
                });
            }
            $fkExists2 = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reembolsos' AND CONSTRAINT_NAME = 'reembolsos_usuario_id_foreign' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            if (empty($fkExists2)) {
                Schema::table('reembolsos', function (Blueprint $table) {
                    $table->foreign('usuario_id')
                        ->references('id')
                        ->on('usuarios')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropUnique(['clave_idempotencia']);
            });
        }

        foreach (['clientes', 'conteos_inventario', 'movimientos_inventario', 'categorias', 'movimientos_efectivo', 'configuraciones_negocio'] as $tabla) {
            if (Schema::hasColumn($tabla, 'sucursal_id')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('sucursal_id');
                });
            }
        }
    }
};
