<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckedFieldsToVehicleStateTransfersTable extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_state_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_state_transfers', 'checked_at')) {
                $table->timestamp('checked_at')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('vehicle_state_transfers', 'checked_by_id')) {
                $table->unsignedBigInteger('checked_by_id')->nullable()->after('checked_at');
                $table->foreign('checked_by_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_state_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_state_transfers', 'checked_by_id')) {
                $table->dropForeign(['checked_by_id']);
                $table->dropColumn('checked_by_id');
            }

            if (Schema::hasColumn('vehicle_state_transfers', 'checked_at')) {
                $table->dropColumn('checked_at');
            }
        });
    }
}
