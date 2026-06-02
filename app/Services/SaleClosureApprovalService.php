<?php

namespace App\Services;

use App\Models\SaleClosureApproval;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleClientPayment;
use App\Models\VehicleTradeIn;

class SaleClosureApprovalService
{
    public function createForPayment(Vehicle $vehicle, ?User $user, VehicleClientPayment $payment): ?SaleClosureApproval
    {
        return $this->createIfClosed($vehicle, $user, SaleClosureApproval::TRIGGER_PAYMENT, $payment->id);
    }

    public function createForTradeIn(Vehicle $vehicle, ?User $user, VehicleTradeIn $tradeIn): ?SaleClosureApproval
    {
        return $this->createIfClosed($vehicle, $user, SaleClosureApproval::TRIGGER_TRADE_IN, $tradeIn->id);
    }

    public function snapshot(Vehicle $vehicle): array
    {
        $vehicle = $vehicle->fresh() ?: $vehicle;

        $salesTotal = (float) ($vehicle->pvp ?? 0)
            + (float) ($vehicle->sales_iuc ?? 0)
            + (float) ($vehicle->sales_tow ?? 0)
            + (float) ($vehicle->sales_transfer ?? 0)
            + (float) ($vehicle->sales_others ?? 0);

        $clientPaymentsTotal = (float) $vehicle->client_payments()->sum('amount');
        $tradeInsTotal = (float) $vehicle->trade_ins()
            ->where('status', VehicleTradeIn::STATUS_CONVERTED)
            ->sum('amount');

        return [
            'sales_total' => round($salesTotal, 2),
            'client_payments_total' => round($clientPaymentsTotal, 2),
            'trade_ins_total' => round($tradeInsTotal, 2),
            'outstanding_amount' => round($salesTotal - $clientPaymentsTotal - $tradeInsTotal, 2),
        ];
    }

    private function createIfClosed(Vehicle $vehicle, ?User $user, string $triggerType, ?int $triggerId): ?SaleClosureApproval
    {
        $snapshot = $this->snapshot($vehicle);

        if ($snapshot['outstanding_amount'] > 0.004) {
            return null;
        }

        $alreadyOpenOrApproved = SaleClosureApproval::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', [SaleClosureApproval::STATUS_PENDING, SaleClosureApproval::STATUS_APPROVED])
            ->exists();

        if ($alreadyOpenOrApproved) {
            return null;
        }

        return SaleClosureApproval::create($snapshot + [
            'vehicle_id' => $vehicle->id,
            'closed_by_id' => $user?->id,
            'trigger_type' => $triggerType,
            'trigger_id' => $triggerId,
            'status' => SaleClosureApproval::STATUS_PENDING,
            'closed_at' => now(),
        ]);
    }
}
