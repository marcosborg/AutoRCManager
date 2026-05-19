<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_lot_items', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_lot_items', 'registration_amount')) {
                $table->decimal('registration_amount', 15, 2)->default(0)->after('adjusted_price');
            }

            if (! Schema::hasColumn('vehicle_lot_items', 'tow_amount')) {
                $table->decimal('tow_amount', 15, 2)->default(0)->after('registration_amount');
            }
        });

        if (Schema::hasColumn('vehicle_groups', 'registration_amount') && Schema::hasColumn('vehicle_groups', 'tow_amount')) {
            $groups = DB::table('vehicle_groups')
                ->select('id', 'registration_amount', 'tow_amount')
                ->where(function ($query) {
                    $query->where('registration_amount', '>', 0)
                        ->orWhere('tow_amount', '>', 0);
                })
                ->get();

            foreach ($groups as $group) {
                $items = DB::table('vehicle_lot_items')
                    ->where('vehicle_group_id', $group->id)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->get(['id']);

                if ($items->isEmpty()) {
                    continue;
                }

                $registrationAmounts = $this->splitAmount((float) ($group->registration_amount ?? 0), $items->count());
                $towAmounts = $this->splitAmount((float) ($group->tow_amount ?? 0), $items->count());

                foreach ($items->values() as $index => $item) {
                    DB::table('vehicle_lot_items')
                        ->where('id', $item->id)
                        ->update([
                            'registration_amount' => $registrationAmounts[$index],
                            'tow_amount' => $towAmounts[$index],
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('vehicle_lot_items', function (Blueprint $table) {
            foreach (['registration_amount', 'tow_amount'] as $column) {
                if (Schema::hasColumn('vehicle_lot_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function splitAmount(float $amount, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        $amounts = [];
        $allocated = 0.0;

        for ($i = 0; $i < $parts; $i++) {
            $value = $i === $parts - 1
                ? $amount - $allocated
                : round($amount / $parts, 2);
            $allocated += $value;
            $amounts[] = round($value, 2);
        }

        return $amounts;
    }
};
