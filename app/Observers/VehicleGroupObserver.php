<?php

namespace App\Observers;

use App\Models\VehicleGroup;
use App\Services\ManagementAlertService;

class VehicleGroupObserver
{
    public function created(VehicleGroup $lot): void
    {
        app(ManagementAlertService::class)->lotPending($lot);
    }

    public function updated(VehicleGroup $lot): void
    {
        if ($lot->isDirty('approved_at')) {
            app(ManagementAlertService::class)->lotPending($lot);
        }
    }
}
