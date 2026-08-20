<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LimpiarTransaccional extends Command
{
    protected $signature = 'clean-transactional
        {--force : Confirmar la limpieza en producción}
        {--solo-superadmin : Vaciar también los datos maestros (bares, sucursales, membresías, usuarios) dejando solo el acceso super_admin}';

    protected $description = 'Limpia todas las tablas transaccionales (ventas, caja, inventario, compras, catálogo e imágenes) sin borrar configuración. Con --solo-superadmin deja la base de datos vacía con solo acceso super_admin.';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes usar: php artisan clean-transactional --force');

            return self::FAILURE;
        }

        $soloSuperadmin = $this->option('solo-superadmin');

        $mensaje = $soloSuperadmin
            ? 'Se vaciará la base de datos completa (transaccional + bares, sucursales, membresías, usuarios), dejando únicamente el acceso super_admin. ¿Continuar?'
            : 'Se eliminarán ventas, caja, inventario, compras, catálogo, contratos, pagos, clientes, proveedores, auditorías e imágenes. ¿Continuar?';

        if (!$this->option('force') && !$this->confirm($mensaje)) {
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

            if ($soloSuperadmin) {
                DB::table('password_reset_tokens')->delete();
                DB::table('pin_intentos')->delete();
                DB::table('impresoras')->delete();
                DB::table('configuraciones_negocio')->delete();
                DB::table('membresias_negocio')->delete();
                DB::table('sucursales')->delete();
                DB::table('negocios')->delete();
                DB::table('roles')->whereNotNull('negocio_id')->delete();
                DB::table('usuarios')
                    ->where(function ($q) {
                        $q->whereNull('rol')->orWhere('rol', '!=', 'super_admin');
                    })
                    ->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Storage::disk('public')->deleteDirectory('productos');
        Storage::disk('public')->deleteDirectory('categorias');

        if ($soloSuperadmin) {
            $superAdmins = DB::table('usuarios')->where('rol', 'super_admin')->count();
            $this->info('Limpieza completa con solo-superadmin: la base de datos quedó vacía.');
            $this->line("Usuarios super_admin restantes: {$superAdmins}.");

            if ($superAdmins === 0) {
                $this->warn('No quedó ningún super_admin. Regístralo ejecutando un seeder de super_admin o crea el usuario manualmente.');

                return self::FAILURE;
            }
        } else {
            $this->info('Limpieza clean-transactional completada.');
            $this->line('Se conservaron usuarios, configuración, impresoras, sucursales y estructura de base de datos.');
        }

        return self::SUCCESS;
    }
}
