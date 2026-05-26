<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GrantPartOrderPermissionsToManagementRoles extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('title', [
                'part_order_access', 'part_order_create', 'part_order_edit', 'part_order_show',
                'part_payment_access', 'part_payment_create', 'part_payment_edit', 'part_payment_show',
                'part_receipt_access', 'part_receipt_create', 'part_receipt_edit', 'part_receipt_show',
            ])
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->whereIn('title', ['Adm', 'Aux. oficina', 'Aux. Oficina', 'Aux. gestão', 'Gestão'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
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
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('title', [
                'part_order_access', 'part_order_create', 'part_order_edit', 'part_order_show',
                'part_payment_access', 'part_payment_create', 'part_payment_edit', 'part_payment_show',
                'part_receipt_access', 'part_receipt_create', 'part_receipt_edit', 'part_receipt_show',
            ])
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->whereIn('title', ['Aux. oficina', 'Aux. Oficina', 'Aux. gestão', 'Gestão'])
            ->pluck('id');

        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->whereIn('role_id', $roleIds)
            ->delete();
    }
}
