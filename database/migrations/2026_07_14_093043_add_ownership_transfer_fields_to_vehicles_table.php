<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dateTime('ownership_documents_ready_at')->nullable();
            $table->dateTime('ownership_payments_completed_at')->nullable();
            $table->dateTime('ownership_rafael_authorized_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'ownership_documents_ready_at',
                'ownership_payments_completed_at',
                'ownership_rafael_authorized_at',
            ]);
        });
    }
};
