<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_consignments', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_consignments', 'to_unit_name')) {
                $table->string('to_unit_name')->nullable()->after('to_unit_id');
            }
        });

        DB::statement('ALTER TABLE vehicle_consignments MODIFY to_unit_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::table('vehicle_consignments')
            ->whereNull('to_unit_id')
            ->update(['to_unit_id' => DB::raw('from_unit_id')]);

        DB::statement('ALTER TABLE vehicle_consignments MODIFY to_unit_id BIGINT UNSIGNED NOT NULL');

        Schema::table('vehicle_consignments', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_consignments', 'to_unit_name')) {
                $table->dropColumn('to_unit_name');
            }
        });
    }
};
