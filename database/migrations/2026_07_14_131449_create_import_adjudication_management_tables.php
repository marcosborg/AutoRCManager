<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchasing_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_import_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained('vehicles')->cascadeOnDelete();
            $table->string('decision');
            $table->timestamp('decision_at');
            $table->date('deadline_at');
            $table->timestamp('agency_documents_sent_at')->nullable();
            $table->timestamp('documents_received_at')->nullable();
            $table->string('previous_license')->nullable();
            $table->string('new_license')->nullable();
            $table->timestamp('new_license_received_at')->nullable();
            $table->timestamp('scrapped_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('operational_alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('calendar_tasks', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('notes')->constrained('vehicles')->cascadeOnDelete();
            $table->string('recipient_group')->nullable()->after('vehicle_id');
            $table->foreignId('assigned_to_id')->nullable()->after('recipient_group')->constrained('users')->nullOnDelete();
            $table->string('type')->nullable()->after('assigned_to_id');
            $table->string('dedupe_key')->nullable()->unique()->after('type');
            $table->string('target_url')->nullable()->after('dedupe_key');
            $table->index(['recipient_group', 'completed_at', 'due_date'], 'calendar_tasks_recipient_due_idx');
            $table->index(['assigned_to_id', 'completed_at', 'due_date'], 'calendar_tasks_assignee_due_idx');
        });

        collect(['ARC', 'RRS', 'GER'])
            ->merge(DB::table('vehicles')->whereNotNull('our_registration')->where('our_registration', '<>', '')->distinct()->pluck('our_registration'))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->each(fn ($name) => DB::table('purchasing_companies')->insert([
                'name' => $name,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        DB::table('operational_alert_recipients')->insert([
            'key' => 'tolls',
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionRoles = [
            'vehicle_import_process_access' => ['Admin', 'Adm', 'Gestão', 'Gestao', 'Aux. gestão', 'Aux. gestao'],
            'vehicle_import_process_edit' => ['Admin', 'Adm', 'Gestão', 'Gestao', 'Aux. gestão', 'Aux. gestao'],
            'purchasing_company_manage' => ['Admin', 'Adm', 'Gestão', 'Gestao', 'Aux. gestão', 'Aux. gestao'],
            'import_configuration_access' => ['Admin', 'Adm', 'Gestão', 'Gestao'],
        ];

        foreach ($permissionRoles as $permissionTitle => $roleTitles) {
            $permissionId = DB::table('permissions')->where('title', $permissionTitle)->value('id')
                ?: DB::table('permissions')->insertGetId([
                    'title' => $permissionTitle,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach (DB::table('roles')->whereIn('title', $roleTitles)->pluck('id') as $roleId) {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionTitles = [
            'vehicle_import_process_access',
            'vehicle_import_process_edit',
            'purchasing_company_manage',
            'import_configuration_access',
        ];
        $permissionIds = DB::table('permissions')->whereIn('title', $permissionTitles)->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::table('calendar_tasks', function (Blueprint $table) {
            $table->dropIndex('calendar_tasks_recipient_due_idx');
            $table->dropIndex('calendar_tasks_assignee_due_idx');
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['assigned_to_id']);
            $table->dropColumn(['vehicle_id', 'recipient_group', 'assigned_to_id', 'type', 'dedupe_key', 'target_url']);
        });

        Schema::dropIfExists('operational_alert_recipients');
        Schema::dropIfExists('vehicle_import_processes');
        Schema::dropIfExists('purchasing_companies');
    }
};
