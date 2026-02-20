<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('timelogs', function (Blueprint $table) {
            if (! Schema::hasColumn('timelogs', 'repair_id')) {
                $table->unsignedBigInteger('repair_id')->nullable()->after('id');
                $table->foreign('repair_id')->references('id')->on('repairs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('timelogs', function (Blueprint $table) {
            if (Schema::hasColumn('timelogs', 'repair_id')) {
                $table->dropForeign(['repair_id']);
                $table->dropColumn('repair_id');
            }
        });
    }
};
