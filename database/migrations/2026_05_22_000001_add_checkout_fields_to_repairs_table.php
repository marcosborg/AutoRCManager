<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repairs')) {
            return;
        }

        Schema::table('repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('repairs', 'kilometers_out')) {
                $table->unsignedInteger('kilometers_out')->nullable()->after('kilometers');
            }

            if (! Schema::hasColumn('repairs', 'fuel_level_in_percentage')) {
                $table->unsignedTinyInteger('fuel_level_in_percentage')->nullable()->after('kilometers_out');
            }

            if (! Schema::hasColumn('repairs', 'fuel_level_percentage')) {
                $table->unsignedTinyInteger('fuel_level_percentage')->nullable()->after('fuel_level_in_percentage');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('repairs')) {
            return;
        }

        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'fuel_level_percentage')) {
                $table->dropColumn('fuel_level_percentage');
            }

            if (Schema::hasColumn('repairs', 'fuel_level_in_percentage')) {
                $table->dropColumn('fuel_level_in_percentage');
            }

            if (Schema::hasColumn('repairs', 'kilometers_out')) {
                $table->dropColumn('kilometers_out');
            }
        });
    }
};
