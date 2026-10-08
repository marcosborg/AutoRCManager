<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicles', 'destination_company')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('destination_company', 40)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Retain company assignments on rollback; older releases ignore this column.
    }
};
