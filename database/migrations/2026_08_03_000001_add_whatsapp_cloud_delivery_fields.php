<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->clearDuplicateExternalIds('chat_messages');
        $this->clearDuplicateExternalIds('lead_whatsapp_notifications');

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->timestamp('provider_status_at')->nullable()->after('sent_at');
            $table->unique('external_id', 'chat_messages_external_id_unique');
        });

        Schema::table('lead_whatsapp_notifications', function (Blueprint $table) {
            $table->timestamp('provider_status_at')->nullable()->after('failed_at');
            $table->unique('external_id', 'lead_whatsapp_notifications_external_id_unique');
        });
    }

    private function clearDuplicateExternalIds(string $table): void
    {
        DB::table($table)
            ->select('external_id')
            ->whereNotNull('external_id')
            ->groupBy('external_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('external_id')
            ->each(function (string $externalId) use ($table): void {
                $keepId = DB::table($table)->where('external_id', $externalId)->min('id');
                DB::table($table)->where('external_id', $externalId)->where('id', '!=', $keepId)->update(['external_id' => null]);
            });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropUnique('chat_messages_external_id_unique');
            $table->dropColumn('provider_status_at');
        });
        Schema::table('lead_whatsapp_notifications', function (Blueprint $table) {
            $table->dropUnique('lead_whatsapp_notifications_external_id_unique');
            $table->dropColumn('provider_status_at');
        });
    }
};
