<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_notes')) {
            Schema::create('vehicle_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('author_name');
                $table->text('body');
                $table->uuid('submission_token');
                $table->timestamps();
                $table->unique(['vehicle_id', 'submission_token']);
                $table->index(['vehicle_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        // Internal notes are historical records: preserve them on rollback.
    }
};
