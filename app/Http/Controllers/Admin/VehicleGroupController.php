<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyVehicleGroupRequest;
use App\Http\Requests\StoreVehicleGroupRequest;
use App\Http\Requests\UpdateVehicleGroupRequest;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use Gate;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class VehicleGroupController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('vehicle_group_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleGroups = VehicleGroup::withCount(['vehicles', 'clients'])->get();

        return view('admin.vehicleGroups.index', compact('vehicleGroups'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_group_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = $this->vehicleOptions();
        $clients = Client::pluck('name', 'id');

        return view('admin.vehicleGroups.create', compact('vehicles', 'clients'));
    }

    public function store(StoreVehicleGroupRequest $request)
    {
        $vehicleGroup = VehicleGroup::create($request->validated());

        $vehicleGroup->vehicles()->sync($request->input('vehicles', []));
        $vehicleGroup->clients()->sync($request->input('clients', []));

        $vehicleGroup->load('vehicles');
        $this->applyWholesalePvpDistribution($vehicleGroup);

        return redirect()->route('admin.vehicle-groups.show', $vehicleGroup->id)->with('message', 'Grupo criado com sucesso');
    }

    public function edit(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_group_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = $this->vehicleOptions();
        $clients = Client::pluck('name', 'id');

        $vehicleGroup->load('vehicles', 'clients');

        return view('admin.vehicleGroups.edit', compact('vehicleGroup', 'vehicles', 'clients'));
    }

    public function update(UpdateVehicleGroupRequest $request, VehicleGroup $vehicleGroup)
    {
        $vehicleGroup->update($request->validated());

        $vehicleGroup->vehicles()->sync($request->input('vehicles', []));
        $vehicleGroup->clients()->sync($request->input('clients', []));

        $vehicleGroup->load('vehicles');
        $this->applyWholesalePvpDistribution($vehicleGroup);

        return redirect()->route('admin.vehicle-groups.show', $vehicleGroup->id)->with('message', 'Grupo atualizado com sucesso');
    }

    public function show(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_group_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleGroup->load(['vehicles.brand', 'vehicles.general_state', 'clients']);
        $operationsByDepartment = ['aquisition' => collect(), 'garage' => collect(), 'sale' => collect()];
        $timelogs = collect();
        $hourPrice = 0;
        $financial = [
            'purchasePrice' => 0.0,
            'purchaseTotal' => 0.0,
            'purchaseBalance' => 0.0,
            'garageTotal' => 0.0,
            'finalSalesTarget' => 0.0,
            'saleTotal' => 0.0,
            'saleBalance' => 0.0,
            'totalMinutes' => 0,
            'labourCost' => 0.0,
            'invested' => 0.0,
            'profit' => 0.0,
            'roi' => 0.0,
            'theoreticalProfit' => 0.0,
        ];
        $vehicleBreakdown = collect();

        return view('admin.vehicleGroups.show', compact(
            'vehicleGroup',
            'operationsByDepartment',
            'timelogs',
            'financial',
            'hourPrice',
            'vehicleBreakdown'
        ));
    }

    public function destroy(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_group_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleGroup->delete();

        return back();
    }

    public function massDestroy(MassDestroyVehicleGroupRequest $request)
    {
        $groups = VehicleGroup::find(request('ids'));

        foreach ($groups as $group) {
            $group->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    private function vehicleOptions(): Collection
    {
        return Vehicle::with('brand')->get()->mapWithKeys(function ($vehicle) {
            $parts = [];

            if ($vehicle->license) {
                $parts[] = $vehicle->license;
            } elseif ($vehicle->foreign_license) {
                $parts[] = $vehicle->foreign_license;
            }

            if ($vehicle->brand && $vehicle->brand->name) {
                $parts[] = $vehicle->brand->name;
            }

            if ($vehicle->model) {
                $parts[] = $vehicle->model;
            }

            $label = trim(implode(' - ', $parts));

            return [$vehicle->id => $label !== '' ? $label : 'Veiculo #' . $vehicle->id];
        });
    }

    private function applyWholesalePvpDistribution(VehicleGroup $vehicleGroup): void
    {
        $wholesalePvp = (float) ($vehicleGroup->wholesale_pvp ?? 0);
        if ($wholesalePvp <= 0) {
            return;
        }

        $vehicles = $vehicleGroup->vehicles;
        if ($vehicles->isEmpty()) {
            return;
        }

        $weights = $vehicles->map(fn($vehicle) => max((float) ($vehicle->pvp ?? 0), 0));
        $weightSum = (float) $weights->sum();

        if ($weightSum <= 0) {
            $weights = $vehicles->map(fn() => 1);
            $weightSum = (float) $weights->sum();
        }

        foreach ($vehicles as $index => $vehicle) {
            $weight = (float) $weights[$index];
            $share = $weightSum > 0 ? $wholesalePvp * ($weight / $weightSum) : 0;
            $newPvp = round($share, 2);

            $vehicle->pvp = $newPvp;
            $vehicle->save();
        }
    }
}
