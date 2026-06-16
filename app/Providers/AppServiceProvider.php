<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Vehicle;
use App\Observers\VehicleObserver;
use App\Models\Repair;
use App\Observers\RepairObserver;
use App\Models\LotPayment;
use App\Models\VehicleConsignment;
use App\Models\VehicleGroup;
use App\Models\VehicleTradeIn;
use App\Observers\LotPaymentObserver;
use App\Observers\VehicleConsignmentObserver;
use App\Observers\VehicleGroupObserver;
use App\Observers\VehicleTradeInObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vehicle::observe(VehicleObserver::class);
        Repair::observe(RepairObserver::class);
        LotPayment::observe(LotPaymentObserver::class);
        VehicleConsignment::observe(VehicleConsignmentObserver::class);
        VehicleGroup::observe(VehicleGroupObserver::class);
        VehicleTradeIn::observe(VehicleTradeInObserver::class);
    }
}
