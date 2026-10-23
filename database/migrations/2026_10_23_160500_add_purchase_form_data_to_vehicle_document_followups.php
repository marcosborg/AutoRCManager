<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('vehicle_document_followups', 'purchase_form_data')) {
            return;
        }

        Schema::table('vehicle_document_followups', function (Blueprint $table) {
            $table->json('purchase_form_data')->nullable();
        });
    }

    public function down(): void
    {
        // Preserve saved purchase forms and their history on a code rollback.
    }
};
