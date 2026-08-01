<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->string('ancho_papel')->default('80mm')->change();
        });
    }

    public function down(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->enum('ancho_papel', ['58mm', '80mm', 'a5'])->default('80mm')->change();
        });
    }
};
