<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionTitles = [
            'vehicle_documents_area_access',
            'vehicle_others_area_access',
        ];

        $roleIds = DB::table('roles')
            ->whereIn('title', ['Stand', 'Stand Adm'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

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
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('title', ['vehicle_documents_area_access', 'vehicle_others_area_access'])
            ->pluck('id');
        $roleIds = DB::table('roles')
            ->whereIn('title', ['Stand', 'Stand Adm'])
            ->pluck('id');

        if ($permissionIds->isEmpty() || $roleIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->whereIn('role_id', $roleIds)
            ->delete();
    }
};
