<?php

namespace App\Observers;

use App\Models\VehicleConsignment;
use App\Services\ManagementAlertService;

class VehicleConsignmentObserver
{
    public function created(VehicleConsignment $consignment): void
    {
        app(ManagementAlertService::class)->consignmentCreated($consignment);
    }
}
