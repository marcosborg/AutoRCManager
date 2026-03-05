<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'iuc_paid_date')) {
                $table->date('iuc_paid_date')->nullable()->after('payment_status_id');
            }

            if (! Schema::hasColumn('vehicles', 'iuc_paid_value')) {
                $table->decimal('iuc_paid_value', 15, 2)->nullable()->after('iuc_paid_date');
            }

            if (! Schema::hasColumn('vehicles', 'tow_paid_date')) {
                $table->date('tow_paid_date')->nullable()->after('iuc_paid_value');
            }

            if (! Schema::hasColumn('vehicles', 'tow_paid_value')) {
                $table->decimal('tow_paid_value', 15, 2)->nullable()->after('tow_paid_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $drop = [];

            foreach (['iuc_paid_date', 'iuc_paid_value', 'tow_paid_date', 'tow_paid_value'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $drop[] = $column;
                }
            }

            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
