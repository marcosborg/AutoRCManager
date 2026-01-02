<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('general_states')) {
            return;
        }

        $exists = DB::table('general_states')
            ->whereRaw('LOWER(name) = ?', ['oficina'])
            ->exists();

        if (! $exists) {
            DB::table('general_states')->insert([
                'name' => 'OFICINA',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('general_states')) {
            return;
        }

        DB::table('general_states')
            ->whereRaw('LOWER(name) = ?', ['oficina'])
            ->delete();
    }
};
