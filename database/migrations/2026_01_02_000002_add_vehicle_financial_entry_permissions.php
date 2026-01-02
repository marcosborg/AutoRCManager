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

        $titles = [
            'vehicle_financial_entry_access',
            'vehicle_financial_entry_create',
            'vehicle_financial_entry_edit',
            'vehicle_financial_entry_show',
            'vehicle_financial_entry_delete',
        ];

        $existing = DB::table('permissions')
            ->whereIn('title', $titles)
            ->pluck('title')
            ->all();

        $missing = array_values(array_diff($titles, $existing));

        if (empty($missing)) {
            return;
        }

        $now = now();
        $rows = array_map(function ($title) use ($now) {
            return [
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $missing);

        DB::table('permissions')->insert($rows);
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->whereIn('title', [
            'vehicle_financial_entry_access',
            'vehicle_financial_entry_create',
            'vehicle_financial_entry_edit',
            'vehicle_financial_entry_show',
            'vehicle_financial_entry_delete',
        ])->delete();
    }
};
