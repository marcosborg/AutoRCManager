<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lot_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('lot_payments', 'bank_amount')) {
                $table->decimal('bank_amount', 15, 2)->default(0)->after('invoiced_amount');
            }

            if (! Schema::hasColumn('lot_payments', 'cash_2_amount')) {
                $table->decimal('cash_2_amount', 15, 2)->default(0)->after('cash_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lot_payments', function (Blueprint $table) {
            foreach (['bank_amount', 'cash_2_amount'] as $column) {
                if (Schema::hasColumn('lot_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
