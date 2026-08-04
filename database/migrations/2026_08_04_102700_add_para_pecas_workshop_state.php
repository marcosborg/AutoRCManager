<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workshop_states')) {
            return;
        }

        $state = DB::table('workshop_states')->where('name', 'Para peças')->first();

        if ($state) {
            DB::table('workshop_states')->where('id', $state->id)->update([
                'position' => 20,
                'is_active' => true,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('workshop_states')->where('position', '>=', 20)->increment('position');

        DB::table('workshop_states')->insert([
            'name' => 'Para peças',
            'position' => 20,
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('workshop_states')) {
            return;
        }

        DB::table('workshop_states')->where('name', 'Para peças')->delete();
        DB::table('workshop_states')->where('position', '>', 20)->decrement('position');
    }
};
