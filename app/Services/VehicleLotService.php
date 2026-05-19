<?php

namespace App\Services;

use App\Models\LotPayment;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use App\Models\VehicleLotItem;
use Illuminate\Support\Facades\DB;

class VehicleLotService
{
    public function syncLotItems(VehicleGroup $lot, array $vehicleIds, array $itemData = []): void
    {
        DB::transaction(function () use ($lot, $vehicleIds, $itemData): void {
            $vehicleIds = array_values(array_unique(array_map('intval', $vehicleIds)));
            $isDiscriminated = $lot->type === 'unitario';
            $subtotal = 0.0;

            $lot->vehicles()->sync($vehicleIds);

            foreach ($vehicleIds as $vehicleId) {
                $price = $isDiscriminated
                    ? $this->decimalOrZero($itemData[$vehicleId]['adjusted_price'] ?? null)
                    : null;
                $registrationAmount = $this->decimalOrZero($itemData[$vehicleId]['registration_amount'] ?? null);
                $towAmount = $this->decimalOrZero($itemData[$vehicleId]['tow_amount'] ?? null);

                if ($isDiscriminated) {
                    $subtotal += $price;
                }

                VehicleLotItem::withTrashed()->updateOrCreate(
                    [
                        'vehicle_group_id' => $lot->id,
                        'vehicle_id' => $vehicleId,
                    ],
                    [
                        'original_price' => 0,
                        'adjusted_price' => $price,
                        'registration_amount' => $registrationAmount,
                        'tow_amount' => $towAmount,
                        'discount' => 0,
                        'allocated_amount' => 0,
                        'paid_amount' => 0,
                        'invoiced_amount' => 0,
                        'cash_amount' => 0,
                        'status' => 'in_lot',
                        'deleted_at' => null,
                    ]
                );
            }

            VehicleLotItem::where('vehicle_group_id', $lot->id)
                ->whereNotIn('vehicle_id', $vehicleIds)
                ->delete();

            if ($isDiscriminated) {
                $lot->updateQuietly([
                    'total_amount' => round($subtotal, 2),
                    'wholesale_pvp' => round($subtotal, 2),
                ]);
            }

            $this->recalculate($lot->fresh(['items.vehicle', 'payments']));
        });
    }

    public function recalculate(VehicleGroup $lot): void
    {
        $lot->loadMissing(['items.vehicle', 'payments']);
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
        $item = VehicleLotItem::with(['lot.payments', 'lot.customer'])
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->first();

        if (! $item || ! $item->lot) {
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

        $lot = $item->lot;
        $target = (float) $lot->effective_total;
        $paid = (float) $lot->approved_paid_total;
        $invoiced = (float) $lot->approved_invoiced_total;
        $bank = (float) $lot->approved_bank_total;
        $cash = (float) $lot->approved_cash_total;
        $cash2 = (float) $lot->approved_cash_2_total;
        $balance = max(0, $target - $paid);
        $itemPrice = $lot->type === 'unitario' ? (float) ($item->adjusted_price ?? 0) : null;
        $itemRegistration = (float) ($item->registration_amount ?? 0);
        $itemTow = (float) ($item->tow_amount ?? 0);

        $status = $lot->approved_at ? 'em_lote' : 'lote_por_aprovar';
        $label = $lot->approved_at ? 'Pertence a lote' : 'Pertence a lote por aprovar';

        return compact('status', 'label', 'target', 'paid', 'invoiced', 'bank', 'cash', 'cash2', 'balance', 'itemPrice', 'itemRegistration', 'itemTow') + ['lot' => $lot];
    }

    private function refreshLotStatus(VehicleGroup $lot): void
    {
        $target = (float) $lot->effective_total;
        $paid = (float) $lot->approved_paid_total;

        $status = 'open';
        if ($paid > 0 && $paid + 0.01 < $target) {
            $status = 'partial';
        }
        if ($target > 0 && $paid + 0.01 >= $target) {
            $status = 'paid';
        }

        $lot->updateQuietly(['status' => $status]);
    }

    private function decimalOrZero($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
