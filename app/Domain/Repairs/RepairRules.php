<?php

namespace App\Domain\Repairs;

use App\Models\Repair;

final class RepairRules
{
    public static function hasOpenRepairs(int $vehicleId): bool
    {
        return Repair::query()
            ->where('vehicle_id', $vehicleId)
            ->get(['repair_state_id', 'repair_finished_at'])
            ->contains(function ($repair) {
                return RepairStatus::isOpen($repair->repair_state_id, $repair->getRawOriginal('repair_finished_at'));
            });
    }
}
