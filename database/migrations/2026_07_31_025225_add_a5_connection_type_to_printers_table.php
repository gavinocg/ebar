<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->enum('connection_type', ['bluetooth', 'wifi', 'lan', 'normal'])->change();
            $table->string('printer_type')->default('thermal')->after('connection_type');
        });
    }

    public function down(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->dropColumn('printer_type');
            $table->enum('connection_type', ['bluetooth', 'wifi', 'lan'])->change();
        });
    }
};
