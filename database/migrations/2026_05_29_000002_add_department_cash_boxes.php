<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cash_boxes')) {
            return;
        }

        foreach (['Caixa Stand', 'Caixa Oficina'] as $name) {
            DB::table('cash_boxes')->updateOrInsert(
                ['slug' => Str::slug($name, '_')],
                [
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cash_boxes')) {
            return;
        }

        DB::table('cash_boxes')
            ->whereIn('slug', ['caixa_stand', 'caixa_oficina'])
            ->delete();
    }
};
