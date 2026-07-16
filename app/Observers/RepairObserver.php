<?php

namespace App\Observers;

use App\Domain\Repairs\RepairStatus;
use App\Models\GeneralState;
use App\Models\Repair;
use App\Models\WorkshopState;

class RepairObserver
{
    public function created(Repair $repair): void
    {
        $this->moveVehicleToWorkshop($repair);
    }

    public function updating(Repair $repair): void
    {
        if ($repair->isDirty('repair_state_id') && $repair->repair_state_id == RepairStatus::CLOSED_ID) {
            $this->moveVehicleToWorkshop($repair);
        }
    }

    private function moveVehicleToWorkshop(Repair $repair): void
    {
        $vehicle = $repair->vehicle;
        if (! $vehicle) {
            return;
        }

        $workshopStateId = GeneralState::query()
            ->whereRaw('LOWER(name) = ?', ['oficina'])
            ->orderBy('id')
            ->value('id');

        if (! $workshopStateId) {
            return;
        }

        $updates = [];
        if ((int) $vehicle->general_state_id !== (int) $workshopStateId) {
            $updates['general_state_id'] = $workshopStateId;
        }

        if (! $vehicle->workshop_state_id) {
            $updates['workshop_state_id'] = WorkshopState::default()?->id;
        }

        if ($updates !== []) {
            $vehicle->update(array_filter($updates));
        }
    }
}
