<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Models\VehicleConsignment;
use App\Models\VehicleConsignmentAudit;

class VehicleConsignmentAuditObserver
{
    public function created(VehicleConsignment $consignment): void
    {
        $this->record('created', $consignment, null, $consignment->getAttributes());
    }

    public function updated(VehicleConsignment $consignment): void
    {
        $before = array_replace($consignment->getAttributes(), $consignment->getOriginal());
        $this->record('updated', $consignment, $before, $consignment->getAttributes());
    }

    public function deleted(VehicleConsignment $consignment): void
    {
        $this->record('deleted', $consignment, $consignment->getOriginal(), null);
    }

    private function record(string $action, VehicleConsignment $consignment, ?array $before, ?array $after): void
    {
        $beforeVehicleId = isset($before['vehicle_id']) ? (int) $before['vehicle_id'] : null;
        $afterVehicleId = isset($after['vehicle_id']) ? (int) $after['vehicle_id'] : null;
        $licenses = Vehicle::withTrashed()
            ->whereIn('id', array_values(array_unique(array_filter([$beforeVehicleId, $afterVehicleId]))))
            ->get()
            ->mapWithKeys(fn (Vehicle $vehicle) => [$vehicle->id => $vehicle->license ?: $vehicle->foreign_license]);
        $user = auth()->user();
        $effectiveSnapshot = $after ?? $before ?? [];

        VehicleConsignmentAudit::query()->create([
            'consignment_id' => $consignment->id,
            'action' => $action,
            'vehicle_id_before' => $beforeVehicleId,
            'vehicle_id_after' => $afterVehicleId,
            'vehicle_license_before' => $beforeVehicleId ? $licenses->get($beforeVehicleId) : null,
            'vehicle_license_after' => $afterVehicleId ? $licenses->get($afterVehicleId) : null,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'ip_address' => request()?->ip(),
            'effective_starts_at' => $effectiveSnapshot['starts_at'] ?? null,
            'effective_ends_at' => $effectiveSnapshot['ends_at'] ?? null,
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
        ]);
    }
}
