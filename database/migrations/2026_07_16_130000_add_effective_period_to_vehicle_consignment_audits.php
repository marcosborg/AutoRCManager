<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_consignment_audits', function (Blueprint $table) {
            $table->dateTime('effective_starts_at')->nullable()->index()->after('ip_address');
            $table->dateTime('effective_ends_at')->nullable()->index()->after('effective_starts_at');
        });

        DB::table('vehicle_consignment_audits')->orderBy('id')->each(function ($audit): void {
            $snapshot = json_decode($audit->after ?: $audit->before ?: '{}', true) ?: [];
            DB::table('vehicle_consignment_audits')->where('id', $audit->id)->update([
                'effective_starts_at' => $snapshot['starts_at'] ?? null,
                'effective_ends_at' => $snapshot['ends_at'] ?? null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_consignment_audits', function (Blueprint $table) {
            $table->dropColumn(['effective_starts_at', 'effective_ends_at']);
        });
    }
};
