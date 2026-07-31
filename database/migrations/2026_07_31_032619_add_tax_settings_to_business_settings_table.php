<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->boolean('charge_tax')->default(true)->after('ticket_message');
            $table->decimal('tax_percentage', 5, 2)->default(16.00)->after('charge_tax');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['charge_tax', 'tax_percentage']);
        });
    }
};
