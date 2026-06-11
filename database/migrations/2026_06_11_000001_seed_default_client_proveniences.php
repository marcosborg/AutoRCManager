<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proveniences')) {
            return;
        }

        $now = now();

        foreach ([
            'Redes sociais',
            'Recomendação',
            'Website',
            'Passagem pelo stand',
            'Outros',
        ] as $name) {
            if (! DB::table('proveniences')->where('name', $name)->exists()) {
                DB::table('proveniences')->insert([
                    'name' => $name,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep collected attribution data intact if this migration is rolled back.
    }
};
