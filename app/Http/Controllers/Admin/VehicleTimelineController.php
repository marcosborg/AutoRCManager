<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\VehicleTimelineService;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class VehicleTimelineController extends Controller
{
    public function show(Vehicle $vehicle, VehicleTimelineService $service)
    {
        abort_if(Gate::denies('vehicle_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $events = $service->buildForVehicle($vehicle);

        $totalCost = (float) $events->where('type', 'cost')->sum('amount');
        $totalRevenue = (float) $events->where('type', 'revenue')->sum('amount');
        $totalCost = abs($totalCost);
        $result = $totalRevenue - $totalCost;

        return view('admin.vehicles.timeline', compact('vehicle', 'events', 'totalCost', 'totalRevenue', 'result'));
    }
}
