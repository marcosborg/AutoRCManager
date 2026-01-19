<?php

namespace App\Domain\Consignments;

use App\Models\Vehicle;
use App\Models\VehicleConsignment;

final class ConsignmentRules
{
    public static function hasActiveConsignment(int $vehicleId): bool
    {
        return VehicleConsignment::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', ConsignmentStatus::ACTIVE)
            ->whereNull('ends_at')
            ->exists();
    }

    public static function shouldBlockSale(Vehicle $vehicle, ?string $incomingSaleDate): bool
    {
        if (! $incomingSaleDate) {
            return false;
        }

        $originalSaleDate = $vehicle->getRawOriginal('sale_date');
        if ($originalSaleDate) {
            return false;
        }

        return self::hasActiveConsignment($vehicle->id);
    }
}
