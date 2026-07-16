<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('title', 'repair_access')->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'title' => 'repair_access',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('roles')->whereIn('title', ['Stand', 'Stand Adm'])->pluck('id') as $roleId) {
            DB::table('permission_role')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('title', ['Stand', 'Stand Adm'])->pluck('id');
        $permissionId = DB::table('permissions')->where('title', 'repair_access')->value('id');

        if ($permissionId) {
            DB::table('permission_role')
                ->whereIn('role_id', $roleIds)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }
};
