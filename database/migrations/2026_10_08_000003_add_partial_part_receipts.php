<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('part_order_items', 'received_quantity')) {
            Schema::table('part_order_items', fn (Blueprint $table) => $table->decimal('received_quantity', 10, 2)->nullable());
        }
        if (! Schema::hasColumn('part_receipts', 'received_items')) {
            Schema::table('part_receipts', fn (Blueprint $table) => $table->json('received_items')->nullable());
        }
    }

    public function down(): void
    {
        // Preserve receipt history and quantities on rollback.
    }
};
