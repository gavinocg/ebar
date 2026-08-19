<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LimpiarTransaccional extends Command
{
    protected $signature = 'clean-transactional {--force : Confirmar la limpieza en producción}';

    protected $description = 'Limpia catálogo, ventas, inventario, caja e imágenes de prueba sin borrar configuración';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes usar: php artisan clean-transactional --force');

            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('Se eliminarán categorías, productos, ventas, inventario, caja e imágenes. ¿Continuar?')) {
            $this->info('Limpieza cancelada.');

            return self::SUCCESS;
        }

        $tablas = [
            'movimientos_efectivo',
            'detalles_venta',
            'movimientos_inventario',
            'ventas',
            'turnos_caja',
            'pagos',
            'contratos',
            'productos',
            'categorias',
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
        $this->line('Se conservaron usuarios, configuración, impresoras, cajas y estructura de base de datos.');

        return self::SUCCESS;
    }
}
