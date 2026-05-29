<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStandAdmRoleForSara extends Migration
{
    private string $standRole = 'Stand';
    private string $standAdmRole = 'Stand Adm';
    private string $saraEmail = 'standcar7@hotmail.com';

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $standRoleId = DB::table('roles')->where('title', $this->standRole)->value('id');
        $standAdmRoleId = DB::table('roles')->where('title', $this->standAdmRole)->value('id');

        if (! $standAdmRoleId) {
            $standAdmRoleId = DB::table('roles')->insertGetId([
                'title' => $this->standAdmRole,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('permission_role')) {
            $permissionIds = collect();

            if ($standRoleId) {
                $permissionIds = DB::table('permission_role')
                    ->where('role_id', $standRoleId)
                    ->pluck('permission_id');
            }

            foreach (['vehicle_trade_in_access', 'vehicle_trade_in_convert'] as $permissionTitle) {
                $permissionId = DB::table('permissions')->where('title', $permissionTitle)->value('id');

                if (! $permissionId) {
                    $permissionId = DB::table('permissions')->insertGetId([
                        'title' => $permissionTitle,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $permissionIds->push($permissionId);
            }

            foreach ($permissionIds->unique()->filter() as $permissionId) {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $standAdmRoleId,
                ]);
            }
        }

        $this->moveSaraToStandAdm($standRoleId, $standAdmRoleId);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $standRoleId = DB::table('roles')->where('title', $this->standRole)->value('id');
        $standAdmRoleId = DB::table('roles')->where('title', $this->standAdmRole)->value('id');

        if (! $standAdmRoleId) {
            return;
        }

        if (Schema::hasTable('role_user')) {
            $saraId = DB::table('users')->where('email', $this->saraEmail)->value('id');

            if ($saraId) {
                DB::table('role_user')
                    ->where('user_id', $saraId)
                    ->where('role_id', $standAdmRoleId)
                    ->delete();

                if ($standRoleId) {
                    DB::table('role_user')->updateOrInsert([
                        'user_id' => $saraId,
                        'role_id' => $standRoleId,
                    ]);
                }
            }
        }

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->where('role_id', $standAdmRoleId)->delete();
        }

        DB::table('roles')->where('id', $standAdmRoleId)->delete();
    }

    private function moveSaraToStandAdm(?int $standRoleId, int $standAdmRoleId): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('role_user')) {
            return;
        }

        $saraId = DB::table('users')->where('email', $this->saraEmail)->value('id');

        if (! $saraId) {
            return;
        }

        if ($standRoleId) {
            DB::table('role_user')
                ->where('user_id', $saraId)
                ->where('role_id', $standRoleId)
                ->delete();
        }

        DB::table('role_user')->updateOrInsert([
            'user_id' => $saraId,
            'role_id' => $standAdmRoleId,
        ]);
    }
}
