<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LimpiarTransaccional extends Command
{
    protected $signature = 'clean-transactional {--force : Confirmar la limpieza en producción}';

    protected $description = 'Limpia todas las tablas transaccionales (ventas, caja, inventario, compras, catálogo e imágenes) sin borrar configuración';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes usar: php artisan clean-transactional --force');

            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('Se eliminarán ventas, caja, inventario, compras, catálogo, contratos, pagos, clientes, proveedores, auditorías e imágenes. ¿Continuar?')) {
            $this->info('Limpieza cancelada.');

            return self::SUCCESS;
        }

        $tablas = [
            'detalles_venta',
            'ventas',
            'reembolsos_detalles',
            'reembolsos',
            'tickets_abiertos_detalles',
            'tickets_abiertos',
            'detalles_orden_compra',
            'ordenes_compra',
            'detalles_conteo',
            'conteos_inventario',
            'movimientos_inventario',
            'movimientos_efectivo',
            'turnos_cajero',
            'pagos',
            'contratos',
            'producto_grupo_modificador',
            'producto_variantes',
            'modificadores',
            'grupos_modificadores',
            'productos',
            'categorias',
            'clientes',
            'proveedores',
            'auditorias',
        ];

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tablas as $tabla) {
                DB::table($tabla)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Storage::disk('public')->deleteDirectory('productos');
        Storage::disk('public')->deleteDirectory('categorias');

        $this->info('Limpieza clean-transactional completada.');
        $this->line('Se conservaron usuarios, configuración, impresoras, sucursales y estructura de base de datos.');

        return self::SUCCESS;
    }
}
