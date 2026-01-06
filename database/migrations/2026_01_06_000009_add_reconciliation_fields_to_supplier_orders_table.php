<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->decimal('invoice_total_confirmed', 15, 2)->nullable()->after('notes');
            $table->decimal('parts_total_confirmed', 15, 2)->nullable()->after('invoice_total_confirmed');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_total_confirmed', 'parts_total_confirmed']);
        });
    }
};
