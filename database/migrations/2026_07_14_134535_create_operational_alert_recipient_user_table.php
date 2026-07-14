<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operational_alert_recipient_user', function (Blueprint $table) {
            $table->unsignedBigInteger('operational_alert_recipient_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['operational_alert_recipient_id', 'user_id'], 'operational_alert_recipient_user_primary');
            $table->foreign('operational_alert_recipient_id', 'op_alert_recipient_user_recipient_fk')
                ->references('id')
                ->on('operational_alert_recipients')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'op_alert_recipient_user_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        DB::table('operational_alert_recipients')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->each(function (object $recipient): void {
                DB::table('operational_alert_recipient_user')->insertOrIgnore([
                    'operational_alert_recipient_id' => $recipient->id,
                    'user_id' => $recipient->user_id,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_alert_recipient_user');
    }
};
