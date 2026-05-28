<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class GrantVehicleCreateToStandRole extends Migration
{
    public function up(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('title', ['Stand'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionId = DB::table('permissions')->where('title', 'vehicle_create')->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'title' => 'vehicle_create',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($roleIds as $roleId) {
            $exists = DB::table('permission_role')
                ->where('permission_id', $permissionId)
                ->where('role_id', $roleId)
                ->exists();

            if (! $exists) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('title', ['Stand'])
            ->pluck('id');

        $permissionId = DB::table('permissions')->where('title', 'vehicle_create')->value('id');

        if ($roleIds->isEmpty() || ! $permissionId) {
            return;
        }

        DB::table('permission_role')
            ->whereIn('role_id', $roleIds)
            ->where('permission_id', $permissionId)
            ->delete();
    }
}
