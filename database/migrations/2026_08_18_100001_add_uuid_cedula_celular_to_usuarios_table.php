<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->after('id');
            $table->char('cedula', 10)->nullable()->after('correo');
            $table->string('celular', 20)->nullable()->after('cedula');
            $table->boolean('debe_cambiar_password')->default(false)->after('remember_token');
        });

        DB::table('usuarios')->orderBy('id')->each(function ($usuario): void {
            DB::table('usuarios')->where('id', $usuario->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->unique('uuid');
            $table->unique('cedula');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropUnique(['cedula']);
            $table->dropColumn(['uuid', 'cedula', 'celular', 'debe_cambiar_password']);
        });
    }
};