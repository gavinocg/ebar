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

        // First, update any NULL turno_caja_id records to reference the first available turno
        DB::table('ventas')
            ->whereNull('turno_caja_id')
            ->update(['turno_caja_id' => DB::raw('(SELECT id FROM turnos_caja ORDER BY id ASC LIMIT 1)')]);

        Schema::table('ventas', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreignId('turno_caja_id')->nullable(false)->change();
            }
            // SQLite doesn't support change, but tests use fresh migrations
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('turno_caja_id')->nullable()->change();
        });
    }
};
