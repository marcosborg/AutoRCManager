<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('title', 'repair_timelogs')
            ->orWhere('title', 'like', 'timelog_%')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        if (Schema::hasTable('timelogs')) {
            Schema::drop('timelogs');
        }
    }

    public function down(): void
    {
        // Timelogs module intentionally removed.
    }
};

