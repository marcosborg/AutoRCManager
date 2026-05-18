<?php

namespace App\Services;

use App\Models\LotPayment;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use App\Models\VehicleLotItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VehicleLotService
{
    public function syncLotItems(VehicleGroup $lot, array $vehicleIds, array $itemData = []): void
    {
        DB::transaction(function () use ($lot, $vehicleIds, $itemData): void {
            $vehicleIds = array_values(array_unique(array_map('intval', $vehicleIds)));

            $lot->vehicles()->sync($vehicleIds);

            foreach ($vehicleIds as $vehicleId) {
                $vehicle = Vehicle::find($vehicleId);
                if (! $vehicle) {
                    continue;
                }

                $data = $itemData[$vehicleId] ?? [];
                $original = $this->decimalOrNull($data['original_price'] ?? null);
                $adjusted = $this->decimalOrNull($data['adjusted_price'] ?? null);

                VehicleLotItem::withTrashed()->updateOrCreate(
                    [
                        'vehicle_group_id' => $lot->id,
                        'vehicle_id' => $vehicleId,
                    ],
                    [
                        'original_price' => $original ?? (float) ($vehicle->pvp ?? 0),
                        'adjusted_price' => $adjusted,
                        'discount' => max(0, (float) (($original ?? $vehicle->pvp ?? 0) - ($adjusted ?? $original ?? $vehicle->pvp ?? 0))),
                        'status' => 'open',
                        'deleted_at' => null,
                    ]
                );
            }

            VehicleLotItem::where('vehicle_group_id', $lot->id)
                ->whereNotIn('vehicle_id', $vehicleIds)
                ->delete();

            $this->recalculate($lot->fresh(['items.vehicle', 'payments']));
        });
    }

    public function recalculate(VehicleGroup $lot): void
    {
        $lot->loadMissing(['items.vehicle', 'payments']);
        $items = $lot->items;

        $this->distributeAmounts($lot, $items);
        $this->distributeApprovedPayments($lot, $items);
        $this->refreshLotStatus($lot);
    }

    public function approvePayment(LotPayment $payment, int $userId): void
    {
        DB::transaction(function () use ($payment, $userId): void {
            $payment->update([
                'approval_status' => LotPayment::STATUS_APPROVED,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $this->recalculate($payment->lot);
        });
    }

    public function rejectPayment(LotPayment $payment, int $userId, ?string $reason): void
    {
        DB::transaction(function () use ($payment, $userId, $reason): void {
            $payment->update([
                'approval_status' => LotPayment::STATUS_REJECTED,
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

            $this->recalculate($payment->lot);
        });
    }

    public function financialStatusForVehicle(Vehicle $vehicle): array
    {
        $item = VehicleLotItem::with(['lot.payments' => function ($query) {
            $query->where('approval_status', LotPayment::STATUS_APPROVED);
        }, 'lot.customer'])
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->first();

        if (! $item || ! $item->lot || ! $item->lot->approved_at) {
            return [
                'status' => 'disponivel',
                'label' => 'Disponivel',
                'lot' => null,
                'target' => 0.0,
                'paid' => 0.0,
                'invoiced' => 0.0,
                'cash' => 0.0,
                'balance' => 0.0,
            ];
        }

        $target = (float) $item->sale_target;
        $paid = (float) $item->paid_amount;
        $invoiced = (float) $item->invoiced_amount;
        $cash = (float) $item->cash_amount;
        $balance = max(0, $target - $paid);

        $status = 'vendida_nao_paga';
        $label = 'Vendida nao paga';

        if ($paid > 0 && $paid + 0.01 < $target) {
            $status = 'parcialmente_paga';
            $label = 'Parcialmente paga';
        }

        if ($target > 0 && $paid + 0.01 >= $target) {
            $status = 'paga';
            $label = 'Paga';
        }

        if ($target > 0 && $invoiced + 0.01 >= $target) {
            $status = 'faturada';
            $label = 'Faturada';
        }

        if ($vehicle->sele_chekout) {
            $status = 'entregue';
            $label = 'Entregue';
        }

        return compact('status', 'label', 'target', 'paid', 'invoiced', 'cash', 'balance') + ['lot' => $item->lot];
    }

    private function distributeAmounts(VehicleGroup $lot, Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        if ($lot->type === 'unitario') {
            foreach ($items as $item) {
                $target = (float) ($item->adjusted_price ?? $item->original_price ?? 0);
                $item->update(['allocated_amount' => round($target, 2)]);
            }
            return;
        }

        $total = (float) ($lot->total_amount ?? $lot->wholesale_pvp ?? 0);
        if ($total <= 0) {
            foreach ($items as $item) {
                $item->update(['allocated_amount' => round((float) ($item->adjusted_price ?? $item->original_price ?? 0), 2)]);
            }
            return;
        }

        $weights = $items->map(fn (VehicleLotItem $item): float => max((float) ($item->adjusted_price ?? $item->original_price ?? $item->vehicle?->pvp ?? 0), 0));
        $weightSum = (float) $weights->sum();

        if ($lot->distribution_mode === 'equal' || $weightSum <= 0) {
            $weights = $items->map(fn (): float => 1.0);
            $weightSum = (float) $weights->sum();
        }

        $allocated = 0.0;
        foreach ($items->values() as $index => $item) {
            $amount = $index === $items->count() - 1
                ? $total - $allocated
                : round($total * ((float) $weights[$index] / $weightSum), 2);
            $allocated += $amount;
            $item->update(['allocated_amount' => round($amount, 2)]);
        }
    }

    private function distributeApprovedPayments(VehicleGroup $lot, Collection $items): void
    {
        foreach ($items as $item) {
            $item->update([
                'paid_amount' => 0,
                'invoiced_amount' => 0,
                'cash_amount' => 0,
            ]);
        }

        $payments = $lot->payments()
            ->where('approval_status', LotPayment::STATUS_APPROVED)
            ->get();

        foreach ($payments as $payment) {
            $this->spreadPaymentValue($items, (float) $payment->amount, 'paid_amount');
            $this->spreadPaymentValue($items, (float) $payment->invoiced_amount, 'invoiced_amount');
            $this->spreadPaymentValue($items, (float) $payment->cash_amount, 'cash_amount');
        }

        foreach ($items as $item) {
            $target = (float) $item->sale_target;
            $status = 'open';
            if ($target > 0 && $item->paid_amount + 0.01 >= $target) {
                $status = 'paid';
            } elseif ($item->paid_amount > 0) {
                $status = 'partial';
            }
            if ($target > 0 && $item->invoiced_amount + 0.01 >= $target) {
                $status = 'invoiced';
            }
            $item->update(['status' => $status]);
        }
    }

    private function spreadPaymentValue(Collection $items, float $value, string $field): void
    {
        if ($value <= 0 || $items->isEmpty()) {
            return;
        }

        $targetSum = (float) $items->sum(fn (VehicleLotItem $item): float => max((float) $item->sale_target, 0));
        if ($targetSum <= 0) {
            $targetSum = (float) $items->count();
        }

        $allocated = 0.0;
        foreach ($items->values() as $index => $item) {
            $weight = max((float) $item->sale_target, 0);
            if ($weight <= 0) {
                $weight = 1.0;
            }

            $amount = $index === $items->count() - 1
                ? $value - $allocated
                : round($value * ($weight / $targetSum), 2);
            $allocated += $amount;
            $item->update([$field => round((float) $item->{$field} + $amount, 2)]);
        }
    }

    private function refreshLotStatus(VehicleGroup $lot): void
    {
        $lot->load('items');
        $target = (float) $lot->items->sum(fn (VehicleLotItem $item): float => $item->sale_target);
        $paid = (float) $lot->items->sum('paid_amount');

        $status = 'open';
        if ($paid > 0 && $paid + 0.01 < $target) {
            $status = 'partial';
        }
        if ($target > 0 && $paid + 0.01 >= $target) {
            $status = 'paid';
        }

        $lot->updateQuietly(['status' => $status]);
    }

    private function decimalOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
