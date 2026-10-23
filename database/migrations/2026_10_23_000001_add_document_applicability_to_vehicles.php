<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('vehicles', 'document_applicability')) return;
        Schema::table('vehicles', function (Blueprint $table) {
            $table->json('document_applicability')->nullable();
        });
    }

    public function down(): void
    {
        // Preserve manual decisions and their history when reverting application code.
    }
};
