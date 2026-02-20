<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('repairs', 'repair_started_at')) {
                $table->dateTime('repair_started_at')->nullable()->after('timestamp');
            }

            if (! Schema::hasColumn('repairs', 'repair_finished_at')) {
                $table->dateTime('repair_finished_at')->nullable()->after('repair_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'repair_finished_at')) {
                $table->dropColumn('repair_finished_at');
            }

            if (Schema::hasColumn('repairs', 'repair_started_at')) {
                $table->dropColumn('repair_started_at');
            }
        });
    }
};

