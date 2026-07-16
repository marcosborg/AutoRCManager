<?php

use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    public function up(): void
    {
        // Intentionally preserved for historical/audit purposes.
        // The application no longer reads or writes this legacy column.
    }

    public function down(): void
    {
        // No schema change was performed in up().
    }
};
