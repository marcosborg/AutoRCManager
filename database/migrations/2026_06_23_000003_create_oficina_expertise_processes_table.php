<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oficina_expertise_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('license')->nullable()->index();
            $table->string('insurance_company')->nullable()->index();
            $table->string('claim_number')->nullable()->index();
            $table->string('process_number')->nullable()->index();
            $table->date('entry_date')->nullable()->index();
            $table->date('scheduled_expertise_date')->nullable()->index();
            $table->string('expert_name')->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->date('approval_date')->nullable();
            $table->date('repair_start_date')->nullable();
            $table->date('expected_repair_date')->nullable();
            $table->date('repair_completed_date')->nullable();
            $table->date('insurance_validation_date')->nullable();
            $table->date('invoice_sent_date')->nullable()->index();
            $table->date('payment_received_date')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->string('status')->default('received')->index();
            $table->string('repair_type')->nullable()->index();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oficina_expertise_process_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('oficina_expertise_processes')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable()->index();
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        $permissionTitles = [
            'oficina_expertise_process_access',
            'oficina_expertise_process_create',
            'oficina_expertise_process_edit',
            'oficina_expertise_process_show',
            'oficina_expertise_process_delete',
            'oficina_expertise_process_change_status',
            'oficina_expertise_process_attachment_access',
            'oficina_expertise_process_attachment_create',
        ];

        $roleIds = DB::table('roles')->whereIn('title', [
            'Admin', 'Adm', 'Chefe oficina', 'Aux. oficina', 'Aux. Oficina', 'Mecânico', 'Mecanico', 'Gestão', 'Gestao',
        ])->pluck('id');

        foreach ($permissionTitles as $title) {
            $permissionId = DB::table('permissions')->where('title', $title)->value('id');
            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($roleIds as $roleId) {
                if (! DB::table('permission_role')->where('permission_id', $permissionId)->where('role_id', $roleId)->exists()) {
                    DB::table('permission_role')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oficina_expertise_process_histories');
        Schema::dropIfExists('oficina_expertise_processes');
    }
};
