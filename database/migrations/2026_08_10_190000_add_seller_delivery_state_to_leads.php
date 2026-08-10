<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('seller_notification_status', 20)->nullable()->after('assigned_user_id')->index();
            $table->foreignId('seller_notified_user_id')->nullable()->after('seller_notification_status')->constrained('users')->nullOnDelete();
            $table->timestamp('seller_notified_at')->nullable()->after('seller_notified_user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['seller_notified_user_id']);
            $table->dropIndex(['seller_notification_status']);
            $table->dropIndex(['seller_notified_at']);
            $table->dropColumn([
                'seller_notification_status',
                'seller_notified_user_id',
                'seller_notified_at',
            ]);
        });
    }
};
