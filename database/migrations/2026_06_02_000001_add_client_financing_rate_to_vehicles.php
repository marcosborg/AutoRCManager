<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'client_financing_rate')) {
                $table->decimal('client_financing_rate', 5, 2)->nullable()->after('client_financed_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'client_financing_rate')) {
                $table->dropColumn('client_financing_rate');
            }
        });
    }
};
