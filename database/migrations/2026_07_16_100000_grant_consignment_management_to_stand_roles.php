<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionTitles = [
            'vehicle_consignment_create',
            'vehicle_consignment_edit',
            'vehicle_consignment_delete',
        ];

        $permissionIds = collect($permissionTitles)->map(function (string $title): int {
            return (int) (DB::table('permissions')->where('title', $title)->value('id')
                ?: DB::table('permissions')->insertGetId([
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
        });

        $roleIds = DB::table('roles')->whereIn('title', ['Stand', 'Stand Adm'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('title', ['Stand', 'Stand Adm'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('title', [
            'vehicle_consignment_create',
            'vehicle_consignment_edit',
            'vehicle_consignment_delete',
        ])->pluck('id');

        DB::table('permission_role')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')->where('title', 'vehicle_consignment_delete')->delete();
    }
};
