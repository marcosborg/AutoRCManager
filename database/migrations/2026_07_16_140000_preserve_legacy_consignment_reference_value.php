<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicle_consignments', 'reference_value')) {
            Schema::table('vehicle_consignments', function (Blueprint $table) {
                $table->decimal('reference_value', 12, 2)->default(0)->after('to_unit_id');
            });
        }
    }

    public function down(): void
    {
        // The legacy value is deliberately never removed by rollback.
    }
};
