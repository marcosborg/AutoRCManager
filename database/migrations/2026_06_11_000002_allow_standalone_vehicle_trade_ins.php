<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_trade_ins', function (Blueprint $table) {
            $table->unsignedBigInteger('sold_vehicle_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('vehicle_trade_ins')->whereNull('sold_vehicle_id')->exists()) {
            return;
        }

        Schema::table('vehicle_trade_ins', function (Blueprint $table) {
            $table->unsignedBigInteger('sold_vehicle_id')->nullable(false)->change();
        });
    }
};
