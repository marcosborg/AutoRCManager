<?php

namespace App\Observers;

use App\Models\VehicleTradeIn;
use App\Services\ManagementAlertService;

class VehicleTradeInObserver
{
    public function created(VehicleTradeIn $tradeIn): void
    {
        app(ManagementAlertService::class)->tradeInReceived($tradeIn);
    }
}
