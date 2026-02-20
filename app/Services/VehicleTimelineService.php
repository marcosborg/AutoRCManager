<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleConsignment;
use App\Models\VehicleStateTransfer;
use App\Models\Repair;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VehicleTimelineService
{
    public function buildForVehicle(Vehicle $vehicle): Collection
    {
        $events = collect();

        $this->addStateTransfers($events, $vehicle->id);
        $this->addConsignments($events, $vehicle->id);
        $this->addRepairs($events, $vehicle->id);

        return $events
            ->sortBy('date_start')
            ->values();
    }

    private function addStateTransfers(Collection $events, int $vehicleId): void
    {
        $transfers = VehicleStateTransfer::with(['from_general_state', 'to_general_state'])
            ->where('vehicle_id', $vehicleId)
            ->orderBy('created_at')
            ->get();

        foreach ($transfers as $transfer) {
            $from = $transfer->from_general_state->name ?? 'N/A';
            $to = $transfer->to_general_state->name ?? 'N/A';

            $events->push([
                'type' => 'state_change',
                'date_start' => Carbon::parse($transfer->created_at),
                'date_end' => null,
                'title' => 'Estado alterado',
                'description' => sprintf('De %s para %s', $from, $to),
                'related_model' => 'VehicleStateTransfer',
                'related_id' => $transfer->id,
                'amount' => null,
                'unit' => null,
                'metadata' => [
                    'from_state_id' => $transfer->from_general_state_id,
                    'to_state_id' => $transfer->to_general_state_id,
                ],
            ]);
        }
    }

    private function addConsignments(Collection $events, int $vehicleId): void
    {
        $consignments = VehicleConsignment::with(['from_unit', 'to_unit'])
            ->where('vehicle_id', $vehicleId)
            ->orderBy('starts_at')
            ->get();

        foreach ($consignments as $consignment) {
            $from = $consignment->from_unit->name ?? 'N/A';
            $to = $consignment->to_unit->name ?? 'N/A';

            $events->push([
                'type' => 'consignment',
                'date_start' => Carbon::parse($consignment->starts_at),
                'date_end' => $consignment->ends_at ? Carbon::parse($consignment->ends_at) : null,
                'title' => 'Consignacao interna',
                'description' => sprintf('De %s para %s', $from, $to),
                'related_model' => 'VehicleConsignment',
                'related_id' => $consignment->id,
                'amount' => $consignment->reference_value,
                'unit' => $to,
                'metadata' => [
                    'from_unit_id' => $consignment->from_unit_id,
                    'to_unit_id' => $consignment->to_unit_id,
                    'status' => $consignment->status,
                ],
            ]);
        }
    }

    private function addRepairs(Collection $events, int $vehicleId): void
    {
        $repairs = Repair::with('repair_state')
            ->where('vehicle_id', $vehicleId)
            ->orderBy('created_at')
            ->get();

        foreach ($repairs as $repair) {
            $state = $repair->repair_state->name ?? 'N/A';
            $start = $repair->timestamp ?: $repair->created_at;

            $events->push([
                'type' => 'repair',
                'date_start' => Carbon::parse($start),
                'date_end' => null,
                'title' => 'Reparacao',
                'description' => sprintf('Estado: %s', $state),
                'related_model' => 'Repair',
                'related_id' => $repair->id,
                'amount' => null,
                'unit' => 'Oficina',
                'metadata' => [
                    'repair_state_id' => $repair->repair_state_id,
                ],
            ]);
        }
    }

}
