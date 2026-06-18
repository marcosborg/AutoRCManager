<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_phone')->nullable()->after('email');
        });

        Schema::create('lead_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('lead_whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('access_token_id')->nullable()->constrained('lead_access_tokens')->nullOnDelete();
            $table->string('phone')->nullable()->index();
            $table->longText('message');
            $table->string('status')->default('pending')->index();
            $table->string('external_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_whatsapp_notifications');
        Schema::dropIfExists('lead_access_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mobile_phone');
        });
    }
};
