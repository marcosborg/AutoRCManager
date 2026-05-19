<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_groups', 'registration_amount')) {
                $table->decimal('registration_amount', 15, 2)->default(0)->after('total_amount');
            }

            if (! Schema::hasColumn('vehicle_groups', 'tow_amount')) {
                $table->decimal('tow_amount', 15, 2)->default(0)->after('registration_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_groups', function (Blueprint $table) {
            foreach (['registration_amount', 'tow_amount'] as $column) {
                if (Schema::hasColumn('vehicle_groups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
