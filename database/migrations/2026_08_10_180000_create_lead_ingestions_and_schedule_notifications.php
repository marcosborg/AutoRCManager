<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_ingestions', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->default('meta_make');
            $table->string('external_id');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('status', 30)->default('received')->index();
            $table->timestamp('source_created_at')->nullable()->index();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_id'], 'lead_ingestions_source_external_unique');
        });

        Schema::table('lead_whatsapp_notifications', function (Blueprint $table) {
            $table->string('delivery_key')->nullable()->after('external_id');
            $table->timestamp('scheduled_for')->nullable()->after('sent_at')->index();
            $table->timestamp('attempted_at')->nullable()->after('scheduled_for');
            $table->unsignedSmallInteger('attempts')->default(0)->after('attempted_at');
            $table->unique('delivery_key', 'lead_whatsapp_notifications_delivery_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('lead_whatsapp_notifications', function (Blueprint $table) {
            $table->dropUnique('lead_whatsapp_notifications_delivery_key_unique');
            $table->dropIndex(['scheduled_for']);
            $table->dropColumn(['delivery_key', 'scheduled_for', 'attempted_at', 'attempts']);
        });

        Schema::dropIfExists('lead_ingestions');
    }
};
