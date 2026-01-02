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
            'client_ledger_entry_access',
            'client_ledger_entry_create',
            'client_ledger_entry_edit',
            'client_ledger_entry_show',
            'client_ledger_entry_delete',
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
            'client_ledger_entry_access',
            'client_ledger_entry_create',
            'client_ledger_entry_edit',
            'client_ledger_entry_show',
            'client_ledger_entry_delete',
        ])->delete();
    }
};
