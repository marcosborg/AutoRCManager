<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('iuc_paid_date')->nullable()->after('payment_status_id');
            $table->decimal('iuc_paid_value', 15, 2)->nullable()->after('iuc_paid_date');
            $table->date('tow_paid_date')->nullable()->after('iuc_paid_value');
            $table->decimal('tow_paid_value', 15, 2)->nullable()->after('tow_paid_date');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'iuc_paid_date',
                'iuc_paid_value',
                'tow_paid_date',
                'tow_paid_value',
            ]);
        });
    }
};
