<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('repairs', 'work_performed')) {
                $table->text('work_performed')->nullable()->after('obs_2');
            }

            if (! Schema::hasColumn('repairs', 'materials_used')) {
                $table->text('materials_used')->nullable()->after('work_performed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'materials_used')) {
                $table->dropColumn('materials_used');
            }

            if (Schema::hasColumn('repairs', 'work_performed')) {
                $table->dropColumn('work_performed');
            }
        });
    }
};
