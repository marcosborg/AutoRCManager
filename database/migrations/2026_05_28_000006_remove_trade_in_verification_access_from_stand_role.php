<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemoveTradeInVerificationAccessFromStandRole extends Migration
{
    public function up(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('title', ['Stand'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('title', ['vehicle_trade_in_access', 'vehicle_trade_in_convert'])
            ->pluck('id');

        DB::table('permission_role')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('title', ['Stand'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        foreach (['vehicle_trade_in_access', 'vehicle_trade_in_convert'] as $permissionTitle) {
            $permissionId = DB::table('permissions')->where('title', $permissionTitle)->value('id');

            if (! $permissionId) {
                continue;
            }

            foreach ($roleIds as $roleId) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }
}
