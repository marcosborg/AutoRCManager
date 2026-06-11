<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_trade_ins', function (Blueprint $table) {
            $table->boolean('has_vehicle_delivery_declaration')
                ->default(false)
                ->after('has_purchase_sale_rgpd');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_trade_ins', function (Blueprint $table) {
            $table->dropColumn('has_vehicle_delivery_declaration');
        });
    }
};
