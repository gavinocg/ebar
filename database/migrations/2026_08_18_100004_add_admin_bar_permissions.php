<?php

use App\Models\Permission;
use App\Models\Rol;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permisos = [
            ['nombre' => 'Cajeros: Actualizar',        'clave' => 'cajero.actualizar',   'modulo' => 'Usuarios'],
            ['nombre' => 'Usuarios: Admins bar',       'clave' => 'usuario.admin_bar',    'modulo' => 'Usuarios'],
        ];

        foreach ($permisos as $p) {
            Permission::updateOrCreate(['clave' => $p['clave']], $p);
        }

        $adminBarPerms = [
            'cajero.actualizar',
            'usuario.cajeros',
        ];

        foreach (Rol::where('slug', 'admin_bar')->get() as $rol) {
            $rol->permisos()->syncWithoutDetaching(
                Permission::whereIn('clave', $adminBarPerms)->pluck('id')
            );
        }

        foreach (Rol::where('slug', 'propietario')->get() as $rol) {
            $rol->permisos()->syncWithoutDetaching(
                Permission::whereIn('clave', ['cajero.actualizar', 'usuario.admin_bar', 'usuario.cajeros'])->pluck('id')
            );
        }

        foreach (Rol::where('slug', 'cajero')->get() as $rol) {
            $rol->permisos()->syncWithoutDetaching(
                Permission::whereIn('clave', ['reembolso.ver', 'reembolso.crear'])->pluck('id')
            );
        }
    }

    public function down(): void
    {
        Permission::whereIn('clave', ['cajero.actualizar', 'usuario.admin_bar'])->delete();
    }
};