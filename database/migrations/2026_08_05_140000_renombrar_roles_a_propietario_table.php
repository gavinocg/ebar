<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('membresias_negocio')->where('rol', 'admin_bar')->update(['rol' => 'propietario']);
        DB::table('usuarios')->whereIn('rol', ['admin_bar', 'administrador'])->update(['rol' => 'propietario']);
    }

    public function down(): void
    {
        DB::table('membresias_negocio')->where('rol', 'propietario')->update(['rol' => 'admin_bar']);
        DB::table('usuarios')->where('rol', 'propietario')->update(['rol' => 'admin_bar']);
    }
};