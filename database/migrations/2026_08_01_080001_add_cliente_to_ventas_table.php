<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('usuario_id')->constrained('clientes')->nullOnDelete();
            $table->string('nombre_cliente')->nullable()->after('cliente_id');
            $table->string('descripcion_cliente')->nullable()->after('nombre_cliente');
            $table->string('entidad_financiera')->nullable()->after('descripcion_cliente');
            $table->string('numero_comprobante_pago')->nullable()->after('entidad_financiera');
            $table->string('estado_cobro', 20)->default('pagado')->after('numero_comprobante_pago');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn([
                'cliente_id', 'nombre_cliente', 'descripcion_cliente',
                'entidad_financiera', 'numero_comprobante_pago', 'estado_cobro',
            ]);
        });
    }
};
