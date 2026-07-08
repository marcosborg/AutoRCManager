<?php

namespace App\Services;

use App\Models\LotPayment;
use App\Models\ManagementAlert;
use App\Models\Vehicle;
use App\Models\VehicleConsignment;
use App\Models\VehicleGroup;
use App\Models\VehicleTradeIn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ManagementAlertService
{
    public function lotPending(VehicleGroup $lot): void
    {
        if ($lot->approved_at) {
            return;
        }

        $this->createOnce(
            ManagementAlert::TYPE_APPROVAL_LOT,
            $lot,
            'Lote por aprovar',
            sprintf('O lote %s aguarda aprovacao formal.', $lot->name ?: '#' . $lot->id),
        );
    }

    public function syncPendingApprovals(): void
    {
        VehicleGroup::whereNull('approved_at')
            ->latest()
            ->limit(200)
            ->get()
            ->each(fn (VehicleGroup $lot) => $this->lotPending($lot));

        LotPayment::where('approval_status', LotPayment::STATUS_PENDING)
            ->latest()
            ->limit(200)
            ->get()
            ->each(fn (LotPayment $payment) => $this->lotPaymentPending($payment));
    }

    public function lotPaymentPending(LotPayment $payment): void
    {
        if ($payment->approval_status !== LotPayment::STATUS_PENDING) {
            return;
        }

        $payment->loadMissing(['lot.customer', 'creator']);

        $this->createOnce(
            ManagementAlert::TYPE_APPROVAL_PAYMENT,
            $payment,
            'Pagamento de lote por aprovar',
            sprintf(
                '%s submeteu um pagamento de %.2f EUR no lote %s.',
                $payment->creator->name ?? 'Um utilizador',
                (float) $payment->amount,
                $payment->lot->name ?? '#' . $payment->vehicle_group_id,
            ),
        );
    }

    public function stockAvailable(Vehicle $vehicle): void
    {
        $this->createOnce(
            ManagementAlert::TYPE_STOCK_AVAILABLE,
            $vehicle,
            'Viatura em stock disponivel',
            sprintf('A viatura %s entrou em stock disponivel.', $this->vehicleLabel($vehicle)),
        );
    }

    public function vehicleSold(Vehicle $vehicle): void
    {
        $this->createOnce(
            ManagementAlert::TYPE_VEHICLE_SOLD,
            $vehicle,
            'Viatura vendida',
            sprintf('A viatura %s foi marcada como vendida.', $this->vehicleLabel($vehicle)),
        );
    }

    public function tradeInReceived(VehicleTradeIn $tradeIn): void
    {
        $this->createOnce(
            ManagementAlert::TYPE_TRADE_IN_RECEIVED,
            $tradeIn,
            'Retoma recebida',
            sprintf('Foi recebida a retoma %s.', $tradeIn->license ?: '#' . $tradeIn->id),
        );
    }

    public function consignmentCreated(VehicleConsignment $consignment): void
    {
        $consignment->loadMissing(['vehicle', 'from_unit', 'to_unit']);

        $this->createOnce(
            ManagementAlert::TYPE_CONSIGNMENT_CREATED,
            $consignment,
            'Consignacao interna criada',
            sprintf(
                '%s foi consignada de %s para %s.',
                $this->vehicleLabel($consignment->vehicle),
                $consignment->from_unit->name ?? 'origem',
                $consignment->to_destination_label ?: 'destino',
            ),
        );
    }

    private function createOnce(string $type, Model $subject, string $title, string $message): void
    {
        ManagementAlert::firstOrCreate(
            ['dedupe_key' => $this->dedupeKey($type, $subject)],
            [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'event_at' => now(),
            ],
        );
    }

    private function dedupeKey(string $type, Model $subject): string
    {
        return Str::of($subject::class)
            ->replace('\\', '.')
            ->append(':', (string) $subject->getKey(), ':', $type)
            ->toString();
    }

    private function vehicleLabel(?Vehicle $vehicle): string
    {
        if (! $vehicle) {
            return 'Viatura';
        }

        return $vehicle->license
            ?: $vehicle->foreign_license
            ?: ('#' . $vehicle->id);
    }
}
