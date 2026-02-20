<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $title = 'repair_timelogs';

        $exists = DB::table('permissions')
            ->where('title', $title)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('permissions')->insert([
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->where('title', 'repair_timelogs')->delete();
    }
};
