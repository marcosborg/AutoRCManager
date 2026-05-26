<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFinancingFieldsToVehicles extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'client_payment_method_info_id')) {
                $table->unsignedBigInteger('client_payment_method_info_id')->nullable()->after('client_id');
                $table->foreign('client_payment_method_info_id')->references('id')->on('payment_methods')->nullOnDelete();
            }

            if (! Schema::hasColumn('vehicles', 'client_financed_amount')) {
                $table->decimal('client_financed_amount', 15, 2)->nullable()->after('client_payment_method_info_id');
            }
        });

        if (Schema::hasTable('payment_methods')) {
            $exists = DB::table('payment_methods')
                ->whereRaw('LOWER(name) = ?', ['financiamento'])
                ->exists();

            if (! $exists) {
                DB::table('payment_methods')->insert([
                    'name' => 'Financiamento',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'client_payment_method_info_id')) {
                $table->dropForeign(['client_payment_method_info_id']);
                $table->dropColumn('client_payment_method_info_id');
            }

            if (Schema::hasColumn('vehicles', 'client_financed_amount')) {
                $table->dropColumn('client_financed_amount');
            }
        });
    }
}
