<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleConsignment;

class VehicleContextService
{
    public function forVehicle(Vehicle $vehicle): array
    {
        $vehicle->loadMissing(['general_state', 'workshop_state']);
        $at = now();
        $locations = $vehicle->locations()->with('operational_unit')
            ->where('starts_at', '<=', $at)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->limit(2)->get();
        $consignments = $vehicle->consignments()->with('to_unit')
            ->where('status', VehicleConsignment::STATUS_ACTIVE)
            ->where('starts_at', '<=', $at)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->limit(2)->get();

        return [
            'general_state' => $vehicle->general_state?->name ?: 'Por definir',
            'workshop_state' => $vehicle->workshop_state?->name ?: 'Por definir',
            'location' => $locations->count() === 1 ? ($locations->first()->operational_unit?->name ?: 'Por confirmar') : 'Por confirmar',
            'location_conflict' => $locations->count() > 1,
            'storage_location' => trim((string) $vehicle->storage_location),
            'consignment_destination' => $consignments->count() === 1 ? $consignments->first()->to_destination_label : null,
            'consignment_conflict' => $consignments->count() > 1,
        ];
    }
}
