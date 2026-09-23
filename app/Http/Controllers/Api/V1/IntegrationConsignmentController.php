<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleConsignmentRequest;
use App\Http\Requests\UpdateVehicleConsignmentRequest;
use App\Models\VehicleConsignment;
use App\Services\VehicleConsignmentService;
use Illuminate\Support\Facades\Gate;

class IntegrationConsignmentController extends Controller
{
    public function store(StoreVehicleConsignmentRequest $request, VehicleConsignmentService $service)
    {
        $consignment = $service->createConsignment($request->validated());

        return response()->json(['data' => $consignment], 201);
    }

    public function update(UpdateVehicleConsignmentRequest $request, VehicleConsignment $consignment, VehicleConsignmentService $service)
    {
        return response()->json(['data' => $service->updateConsignment($consignment, $request->validated())]);
    }

    public function destroy(VehicleConsignment $consignment, VehicleConsignmentService $service)
    {
        Gate::authorize('vehicle_consignment_delete');
        $service->deleteConsignment($consignment);

        return response()->noContent();
    }
}
