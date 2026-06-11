<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('external_services')) {
            return;
        }

        Schema::table('external_services', function (Blueprint $table) {
            if (Schema::hasColumn('external_services', 'repair_id')) {
                $table->dropConstrainedForeignId('repair_id');
            }
            if (Schema::hasColumn('external_services', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('external_services')) {
            return;
        }

        Schema::table('external_services', function (Blueprint $table) {
            if (! Schema::hasColumn('external_services', 'repair_id')) {
                $table->foreignId('repair_id')->nullable()->constrained('repairs')->nullOnDelete();
            }
            if (! Schema::hasColumn('external_services', 'invoice_number')) {
                $table->string('invoice_number')->nullable();
            }
        });
    }
};
