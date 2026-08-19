<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Punto de Venta
            ['nombre' => 'Punto de Venta: Ver',         'clave' => 'pos.ver',           'modulo' => 'Punto de Venta'],
            ['nombre' => 'Punto de Venta: Cobrar',       'clave' => 'pos.cobrar',        'modulo' => 'Punto de Venta'],
            ['nombre' => 'Punto de Venta: Tickets',      'clave' => 'pos.tickets',       'modulo' => 'Punto de Venta'],
            ['nombre' => 'Punto de Venta: Caja',         'clave' => 'pos.caja',          'modulo' => 'Punto de Venta'],

            // Productos
            ['nombre' => 'Productos: Crear',             'clave' => 'producto.crear',    'modulo' => 'Productos'],
            ['nombre' => 'Productos: Ver',               'clave' => 'producto.ver',      'modulo' => 'Productos'],
            ['nombre' => 'Productos: Editar',            'clave' => 'producto.editar',   'modulo' => 'Productos'],
            ['nombre' => 'Productos: Eliminar',          'clave' => 'producto.eliminar', 'modulo' => 'Productos'],
            ['nombre' => 'Productos: Importar',          'clave' => 'producto.importar', 'modulo' => 'Productos'],
            ['nombre' => 'Productos: Exportar',          'clave' => 'producto.exportar', 'modulo' => 'Productos'],

            // Categorías
            ['nombre' => 'Categorías: Crear',            'clave' => 'categoria.crear',    'modulo' => 'Categorías'],
            ['nombre' => 'Categorías: Ver',              'clave' => 'categoria.ver',      'modulo' => 'Categorías'],
            ['nombre' => 'Categorías: Editar',           'clave' => 'categoria.editar',   'modulo' => 'Categorías'],
            ['nombre' => 'Categorías: Eliminar',         'clave' => 'categoria.eliminar', 'modulo' => 'Categorías'],

            // Ventas
            ['nombre' => 'Ventas: Ver',                  'clave' => 'venta.ver',          'modulo' => 'Ventas'],
            ['nombre' => 'Ventas: Administrar',          'clave' => 'venta.administrar',  'modulo' => 'Ventas'],

            // Clientes
            ['nombre' => 'Clientes: Crear',              'clave' => 'cliente.crear',      'modulo' => 'Clientes'],
            ['nombre' => 'Clientes: Ver',                'clave' => 'cliente.ver',        'modulo' => 'Clientes'],
            ['nombre' => 'Clientes: Editar',             'clave' => 'cliente.editar',     'modulo' => 'Clientes'],

            // Inventario
            ['nombre' => 'Inventario: Ver',              'clave' => 'inventario.ver',      'modulo' => 'Inventario'],
            ['nombre' => 'Inventario: Ajustar',          'clave' => 'inventario.ajustar',  'modulo' => 'Inventario'],
            ['nombre' => 'Inventario: Conteos',          'clave' => 'inventario.conteos',  'modulo' => 'Inventario'],

            // Proveedores
            ['nombre' => 'Proveedores: Crear',           'clave' => 'proveedor.crear',    'modulo' => 'Proveedores'],
            ['nombre' => 'Proveedores: Ver',             'clave' => 'proveedor.ver',      'modulo' => 'Proveedores'],
            ['nombre' => 'Proveedores: Editar',          'clave' => 'proveedor.editar',   'modulo' => 'Proveedores'],
            ['nombre' => 'Proveedores: Eliminar',        'clave' => 'proveedor.eliminar', 'modulo' => 'Proveedores'],

            // Órdenes de Compra
            ['nombre' => 'Órdenes: Crear',               'clave' => 'orden.crear',        'modulo' => 'Órdenes de Compra'],
            ['nombre' => 'Órdenes: Ver',                 'clave' => 'orden.ver',          'modulo' => 'Órdenes de Compra'],
            ['nombre' => 'Órdenes: Recibir',             'clave' => 'orden.recibir',      'modulo' => 'Órdenes de Compra'],
            ['nombre' => 'Órdenes: Eliminar',            'clave' => 'orden.eliminar',     'modulo' => 'Órdenes de Compra'],

            // Reportes
            ['nombre' => 'Reportes: Ventas',             'clave' => 'reporte.ventas',        'modulo' => 'Reportes'],
            ['nombre' => 'Reportes: Productos',          'clave' => 'reporte.productos',     'modulo' => 'Reportes'],
            ['nombre' => 'Reportes: Categorías',         'clave' => 'reporte.categorias',    'modulo' => 'Reportes'],
            ['nombre' => 'Reportes: Métodos de Pago',    'clave' => 'reporte.metodos_pago',  'modulo' => 'Reportes'],
            ['nombre' => 'Reportes: Tendencias',         'clave' => 'reporte.tendencias',    'modulo' => 'Reportes'],
            ['nombre' => 'Reportes: Sucursal',           'clave' => 'reporte.sucursal',      'modulo' => 'Reportes'],
            ['nombre' => 'Reportes: Inventario',         'clave' => 'reporte.inventario',    'modulo' => 'Reportes'],
            ['nombre' => 'Reportes: Cajeros',            'clave' => 'reporte.cajeros',       'modulo' => 'Reportes'],

            // Caja
            ['nombre' => 'Caja: Administrar',            'clave' => 'caja.administrar',   'modulo' => 'Caja'],
            ['nombre' => 'Caja: Reporte',                'clave' => 'caja.reporte',       'modulo' => 'Caja'],
            ['nombre' => 'Caja: Reabrir',                'clave' => 'caja.reabrir',       'modulo' => 'Caja'],

            // Usuarios
            ['nombre' => 'Usuarios: Cajeros',            'clave' => 'usuario.cajeros',    'modulo' => 'Usuarios'],
            ['nombre' => 'Usuarios: Admins bar',         'clave' => 'usuario.admin_bar',  'modulo' => 'Usuarios'],

            // Configuración
            ['nombre' => 'Configuración: Negocio',       'clave' => 'configuracion.negocio', 'modulo' => 'Configuración'],

            // Impresoras
            ['nombre' => 'Impresoras: Crear',            'clave' => 'impresora.crear',    'modulo' => 'Impresoras'],
            ['nombre' => 'Impresoras: Ver',              'clave' => 'impresora.ver',      'modulo' => 'Impresoras'],
            ['nombre' => 'Impresoras: Editar',           'clave' => 'impresora.editar',   'modulo' => 'Impresoras'],
            ['nombre' => 'Impresoras: Eliminar',         'clave' => 'impresora.eliminar', 'modulo' => 'Impresoras'],

            // Sucursales
            ['nombre' => 'Sucursales: Crear',            'clave' => 'sucursal.crear',     'modulo' => 'Sucursales'],
            ['nombre' => 'Sucursales: Ver',              'clave' => 'sucursal.ver',       'modulo' => 'Sucursales'],
            ['nombre' => 'Sucursales: Editar',           'clave' => 'sucursal.editar',    'modulo' => 'Sucursales'],
            ['nombre' => 'Sucursales: Eliminar',         'clave' => 'sucursal.eliminar',  'modulo' => 'Sucursales'],

            // Reembolsos
            ['nombre' => 'Reembolsos: Ver',              'clave' => 'reembolso.ver',      'modulo' => 'Reembolsos'],
            ['nombre' => 'Reembolsos: Crear',            'clave' => 'reembolso.crear',    'modulo' => 'Reembolsos'],

            // Tickets Abiertos
            ['nombre' => 'Tickets: Ver',                 'clave' => 'ticket.ver',         'modulo' => 'Tickets'],
            ['nombre' => 'Tickets: Crear',               'clave' => 'ticket.crear',       'modulo' => 'Tickets'],
            ['nombre' => 'Tickets: Eliminar',            'clave' => 'ticket.eliminar',    'modulo' => 'Tickets'],

            // Cuadres
            ['nombre' => 'Cuadres: Ver',                 'clave' => 'cuadre.ver',         'modulo' => 'Cuadres'],
            ['nombre' => 'Cuadres: Aprobar',             'clave' => 'cuadre.aprobar',     'modulo' => 'Cuadres'],
            ['nombre' => 'Cuadres: Rechazar',            'clave' => 'cuadre.rechazar',    'modulo' => 'Cuadres'],

            // Auditoría
            ['nombre' => 'Auditoría: Ver',               'clave' => 'auditoria.ver',      'modulo' => 'Auditoría'],

            // Roles
            ['nombre' => 'Roles: Gestionar',             'clave' => 'rol.gestionar',      'modulo' => 'Roles'],
        ];

        foreach ($permisos as $p) {
            Permission::updateOrCreate(['clave' => $p['clave']], $p);
        }

        // Default roles per business (created lazily via data migration)
        // Here we create the global built-in roles (negocio_id = null)
        $allPermKeys = Permission::pluck('id', 'clave');

        $rolDefs = [
            'cajero' => [
                'nombre' => 'Cajero',
                'slug' => 'cajero',
                'descripcion' => 'Personal de ventas/recaudación. Acceso al POS y caja.',
                'es_sistema' => true,
                'permisos' => ['pos.ver', 'pos.cobrar', 'pos.tickets', 'pos.caja', 'cliente.crear', 'cliente.ver', 'cliente.editar', 'ticket.ver', 'ticket.crear', 'ticket.eliminar', 'cuadre.ver', 'reembolso.ver', 'reembolso.crear'],
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
                    'caja.reporte',
                    'reporte.cajeros',
                    'cajero.actualizar', 'usuario.cajeros',
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

        foreach ($rolDefs as $key => $def) {
            $permIds = [];
            foreach ($def['permisos'] as $clave) {
                if (isset($allPermKeys[$clave])) {
                    $permIds[] = $allPermKeys[$clave];
                }
            }
            unset($def['permisos']);

            $rol = Rol::updateOrCreate(
                ['slug' => $def['slug'], 'negocio_id' => null],
                $def
            );
            $rol->permisos()->sync($permIds);
        }
    }
}
