<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRepairWorkTypeAndRestrictPartOrderPermissions extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('repairs', 'work_type')) {
                $table->string('work_type')->default('workshop')->after('vehicle_id');
            }
        });

        $permissionIds = DB::table('permissions')
            ->whereIn('title', [
                'part_order_access', 'part_order_create', 'part_order_edit', 'part_order_show', 'part_order_delete',
                'part_payment_access', 'part_payment_create', 'part_payment_edit', 'part_payment_show', 'part_payment_delete',
                'part_receipt_access', 'part_receipt_create', 'part_receipt_edit', 'part_receipt_show', 'part_receipt_delete',
            ])
            ->pluck('id');

        $allowedRoleIds = DB::table('roles')
            ->whereIn('title', ['Admin', 'Adm', 'Chefe oficina'])
            ->pluck('id');

        if ($permissionIds->isEmpty() || $allowedRoleIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->whereNotIn('role_id', $allowedRoleIds)
            ->delete();

        foreach ($allowedRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                if (! DB::table('permission_role')->where('role_id', $roleId)->where('permission_id', $permissionId)->exists()) {
                    DB::table('permission_role')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'work_type')) {
                $table->dropColumn('work_type');
            }
        });
    }
}
