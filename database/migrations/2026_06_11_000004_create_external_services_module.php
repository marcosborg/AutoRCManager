<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('external_services')) {
            Schema::create('external_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('suplier_id')->nullable()->constrained('supliers')->nullOnDelete();
                $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('description');
                $table->string('priority')->default('normal');
                $table->string('status')->default('requested');
                $table->unsignedInteger('requested_delivery_days')->nullable();
                $table->date('expected_date')->nullable();
                $table->date('completed_date')->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'expected_date']);
                $table->index('vehicle_id');
            });
        }

        $titles = [
            'external_service_access',
            'external_service_create',
            'external_service_edit',
            'external_service_show',
            'external_service_delete',
        ];

        $roleIds = DB::table('roles')->whereIn('title', [
            'Admin', 'Adm', 'Chefe oficina', 'Aux. oficina', 'Aux. Oficina', 'Aux. gestão', 'Gestão',
        ])->pluck('id');

        foreach ($titles as $title) {
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
                    DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $roleId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('external_services');
    }
};
