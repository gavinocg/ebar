<?php

use App\Models\Permission;
use App\Models\Rol;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Permission::count() === 0) {
            (new RolePermissionSeeder())->run();
        }

        $allPermKeys = Permission::pluck('id', 'clave');

        $roleDefs = [
            'cajero' => [
                'nombre' => 'Cajero',
                'slug' => 'cajero',
                'descripcion' => 'Personal de ventas/recaudación. Acceso al POS y caja.',
                'es_sistema' => true,
                'permisos' => ['pos.ver', 'pos.cobrar', 'pos.tickets', 'pos.caja', 'cliente.crear', 'cliente.ver', 'cliente.editar', 'ticket.ver', 'ticket.crear', 'ticket.eliminar', 'cuadre.ver'],
            ],
            'admin_bar' => [
                'nombre' => 'Administrador de Bar',
                'slug' => 'admin_bar',
                'descripcion' => 'Supervisor de ventas. Gestiona productos, inventario y proveedores.',
                'es_sistema' => true,
                'permisos' => [
                    'pos.ver', 'pos.cobrar', 'pos.tickets', 'pos.caja',
                    'producto.crear', 'producto.ver', 'producto.editar', 'producto.eliminar', 'producto.importar', 'producto.exportar',
                    'categoria.crear', 'categoria.ver', 'categoria.editar', 'categoria.eliminar',
                    'venta.ver', 'venta.administrar',
                    'cliente.crear', 'cliente.ver', 'cliente.editar',
                    'inventario.ver', 'inventario.ajustar', 'inventario.conteos',
                    'proveedor.crear', 'proveedor.ver', 'proveedor.editar', 'proveedor.eliminar',
                    'orden.crear', 'orden.ver', 'orden.recibir', 'orden.eliminar',
                    'reembolso.ver', 'reembolso.crear',
                    'ticket.ver', 'ticket.crear', 'ticket.eliminar',
                    'cuadre.ver', 'cuadre.aprobar', 'cuadre.rechazar',
                    'caja.administrar', 'caja.reporte',
                    'reporte.cajeros',
                ],
            ],
            'propietario' => [
                'nombre' => 'Propietario',
                'slug' => 'propietario',
                'descripcion' => 'Dueño del bar. Acceso completo a configuración, reportes y auditoría.',
                'es_sistema' => true,
                'permisos' => array_keys($allPermKeys->toArray()),
            ],
        ];

        $rolesBySlug = [];

        foreach ($roleDefs as $key => $def) {
            $permIds = [];
            foreach ($def['permisos'] as $clave) {
                if (isset($allPermKeys[$clave])) {
                    $permIds[] = $allPermKeys[$clave];
                }
            }
            unset($def['permisos']);

            // Create role for each business that has this role slug
            $negocios = DB::table('membresias_negocio')
                ->where('rol', $key)
                ->distinct()
                ->pluck('negocio_id');

            foreach ($negocios as $negocioId) {
                $slugKey = $negocioId . '_' . $key;
                if (isset($rolesBySlug[$slugKey])) {
                    $rolId = $rolesBySlug[$slugKey];
                } else {
                    $rol = Rol::updateOrCreate(
                        ['slug' => $key, 'negocio_id' => $negocioId],
                        [
                            'nombre' => $def['nombre'],
                            'descripcion' => $def['descripcion'],
                            'es_sistema' => $def['es_sistema'],
                        ]
                    );
                    $rol->permisos()->sync($permIds);
                    $rolId = $rol->id;
                    $rolesBySlug[$slugKey] = $rolId;
                }

                DB::table('membresias_negocio')
                    ->where('negocio_id', $negocioId)
                    ->where('rol', $key)
                    ->update(['rol_id' => $rolId]);
            }
        }
    }

    public function down(): void
    {
        DB::table('membresias_negocio')->update(['rol_id' => null]);
        DB::table('rol_permiso')->delete();
        DB::table('roles')->delete();
    }
};
