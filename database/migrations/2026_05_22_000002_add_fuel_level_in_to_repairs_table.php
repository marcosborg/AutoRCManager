<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repairs') || Schema::hasColumn('repairs', 'fuel_level_in_percentage')) {
            return;
        }

        Schema::table('repairs', function (Blueprint $table) {
            $table->unsignedTinyInteger('fuel_level_in_percentage')->nullable()->after('kilometers_out');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('repairs') || ! Schema::hasColumn('repairs', 'fuel_level_in_percentage')) {
            return;
        }

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn('fuel_level_in_percentage');
        });
    }
};
