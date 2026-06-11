<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('repairs')->where('work_type', 'paint')->update(['work_type' => 'painting']);

        Schema::create('workshop_intervention_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (['Reparação', 'Chapa', 'Pintura'] as $name) {
            DB::table('workshop_intervention_types')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::create('workshop_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained('repairs')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('workshop_intervention_types')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->string('status')->default('planned');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['planned_start_date', 'planned_end_date']);
            $table->index(['status', 'planned_start_date']);
        });

        Schema::create('workshop_intervention_user', function (Blueprint $table) {
            $table->foreignId('workshop_intervention_id')->constrained('workshop_interventions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['workshop_intervention_id', 'user_id'], 'workshop_intervention_user_pk');
        });

        Schema::table('repair_work_logs', function (Blueprint $table) {
            $table->foreignId('workshop_intervention_id')
                ->nullable()
                ->after('repair_id')
                ->constrained('workshop_interventions')
                ->nullOnDelete();
            $table->index(['user_id', 'finished_at'], 'repair_work_logs_user_open_idx');
        });

        $managementPermissions = [
            'workshop_planning_access',
            'workshop_planning_create',
            'workshop_planning_edit',
            'workshop_planning_delete',
            'workshop_intervention_type_access',
            'workshop_intervention_type_create',
            'workshop_intervention_type_edit',
            'workshop_intervention_type_delete',
        ];
        $executionPermissions = ['workshop_task_access', 'workshop_task_execute'];

        $managerRoleIds = DB::table('roles')->whereIn('title', ['Admin', 'Adm', 'Chefe oficina'])->pluck('id');
        $mechanicRoleIds = DB::table('roles')->whereIn('title', ['Mecânico', 'Mecanico'])->pluck('id');

        foreach ($managementPermissions as $title) {
            $this->grantPermission($title, $managerRoleIds);
        }
        foreach ($executionPermissions as $title) {
            $this->grantPermission($title, $managerRoleIds->merge($mechanicRoleIds)->unique());
        }
    }

    public function down(): void
    {
        Schema::table('repair_work_logs', function (Blueprint $table) {
            $table->dropForeign(['workshop_intervention_id']);
            $table->dropIndex('repair_work_logs_user_open_idx');
            $table->dropColumn('workshop_intervention_id');
        });
        Schema::dropIfExists('workshop_intervention_user');
        Schema::dropIfExists('workshop_interventions');
        Schema::dropIfExists('workshop_intervention_types');
    }

    private function grantPermission(string $title, $roleIds): void
    {
        $permissionId = DB::table('permissions')->where('title', $title)->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'title' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
