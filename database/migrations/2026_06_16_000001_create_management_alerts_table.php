<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('dedupe_key')->unique();
            $table->string('title');
            $table->text('message')->nullable();
            $table->nullableMorphs('subject');
            $table->timestamp('event_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_alerts');
    }
};
