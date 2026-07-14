<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lot_payments', 'vehicle_trade_in_id')) {
            Schema::table('lot_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('vehicle_trade_in_id')->nullable()->unique()->after('payment_method_id');
                $table->foreign('vehicle_trade_in_id')->references('id')->on('vehicle_trade_ins')->nullOnDelete();
            });
        }

        if (! DB::table('payment_methods')->whereRaw('LOWER(name) = ?', ['retoma'])->exists()) {
            DB::table('payment_methods')->insert([
                'name' => 'Retoma',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lot_payments', 'vehicle_trade_in_id')) {
            Schema::table('lot_payments', function (Blueprint $table) {
                $table->dropForeign(['vehicle_trade_in_id']);
                $table->dropUnique(['vehicle_trade_in_id']);
                $table->dropColumn('vehicle_trade_in_id');
            });
        }
    }
};
