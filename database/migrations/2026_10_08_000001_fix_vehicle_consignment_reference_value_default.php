<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicle_consignments', 'reference_value')) {
            Schema::table('vehicle_consignments', function (Blueprint $table) {
                $table->decimal('reference_value', 12, 2)->default(0)->after('to_unit_id');
            });

            return;
        }

        // MySQL and MariaDB: change only the default, preserving historical values and type.
        DB::statement('ALTER TABLE vehicle_consignments ALTER COLUMN reference_value SET DEFAULT 0');
    }

    public function down(): void
    {
        // Keep the historical column and its default: removing either would lose data
        // or make current and previous application versions unable to create consignments.
    }
};
