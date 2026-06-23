<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_access_tokens', function (Blueprint $table) {
            $table->timestamp('first_open_deadline_at')->nullable()->after('expires_at')->index();
            $table->string('revoked_reason')->nullable()->after('revoked_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('lead_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['first_open_deadline_at', 'revoked_reason']);
        });
    }
};
