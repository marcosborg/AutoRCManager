<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $paymentMethodName = 'Transação interna';

    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $existing = DB::table('payment_methods')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->paymentMethodName)])
            ->first();

        if ($existing) {
            DB::table('payment_methods')
                ->where('id', $existing->id)
                ->update([
                    'name' => $this->paymentMethodName,
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('payment_methods')->insert([
            'name' => $this->paymentMethodName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        DB::table('payment_methods')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->paymentMethodName)])
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }
};
