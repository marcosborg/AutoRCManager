<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('general_states')) {
            $exists = DB::table('general_states')
                ->whereRaw('LOWER(name) = ?', ['venda suspensa'])
                ->exists();

            if (! $exists) {
                DB::table('general_states')->insert([
                    'name' => 'Venda Suspensa',
                    'position' => ((int) DB::table('general_states')->max('position')) + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::create('vehicle_suspended_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('previous_general_state_id')->nullable()->constrained('general_states')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamp('suspended_at')->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->timestamp('converted_at')->nullable()->index();
            $table->foreignId('suspended_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_suspended_sales');
    }
};
