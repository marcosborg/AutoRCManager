<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'scheduled_expertise_date',
        'approval_date',
        'repair_start_date',
        'expected_repair_date',
        'repair_completed_date',
        'insurance_validation_date',
        'invoice_sent_date',
        'payment_received_date',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('oficina_expertise_processes')) {
            return;
        }

        foreach ($this->columns as $column) {
            if (Schema::hasColumn('oficina_expertise_processes', $column)) {
                DB::statement("ALTER TABLE oficina_expertise_processes MODIFY {$column} DATETIME NULL");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('oficina_expertise_processes')) {
            return;
        }

        foreach ($this->columns as $column) {
            if (Schema::hasColumn('oficina_expertise_processes', $column)) {
                DB::statement("ALTER TABLE oficina_expertise_processes MODIFY {$column} DATE NULL");
            }
        }
    }
};
