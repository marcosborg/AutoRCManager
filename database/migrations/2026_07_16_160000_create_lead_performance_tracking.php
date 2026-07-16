<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_access_tokens', function (Blueprint $table) {
            $table->foreignId('assignment_history_id')->nullable()->after('user_id')
                ->constrained('lead_assignment_histories')->nullOnDelete();
            $table->index(['assignment_history_id', 'last_used_at'], 'lead_tokens_assignment_open_idx');
        });

        Schema::create('lead_contact_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignment_history_id')->constrained('lead_assignment_histories')->cascadeOnDelete();
            $table->foreignId('access_token_id')->nullable()->constrained('lead_access_tokens')->nullOnDelete();
            $table->string('channel', 20);
            $table->timestamp('clicked_at');
            $table->timestamps();
            $table->index(['assignment_history_id', 'clicked_at'], 'lead_contact_assignment_clicked_idx');
            $table->index(['user_id', 'clicked_at'], 'lead_contact_user_clicked_idx');
        });

        $permissionId = DB::table('permissions')->where('title', 'lead_performance_access')->value('id')
            ?: DB::table('permissions')->insertGetId([
                'title' => 'lead_performance_access',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        foreach (DB::table('roles')->whereIn('title', ['Admin', 'Adm'])->pluck('id') as $roleId) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_contact_events');
        Schema::table('lead_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['assignment_history_id']);
            $table->dropIndex('lead_tokens_assignment_open_idx');
            $table->dropColumn('assignment_history_id');
        });

        $permissionId = DB::table('permissions')->where('title', 'lead_performance_access')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
