<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyVehicleGroupRequest;
use App\Http\Requests\StoreVehicleGroupRequest;
use App\Http\Requests\UpdateVehicleGroupRequest;
use App\Models\AccountOperation;
use App\Models\Client;
use App\Models\Timelog;
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

        return redirect()->route('admin.vehicle-groups.show', $vehicleGroup->id)->with('message', 'Grupo atualizado com sucesso');
    }

    public function show(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_group_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleGroup->load(['vehicles.brand', 'vehicles.general_state', 'clients']);
        $vehicleIds = $vehicleGroup->vehicles->pluck('id')->filter();

        $operations = $vehicleIds->isEmpty()
            ? collect()
            : AccountOperation::with(['account_item.account_category', 'vehicle'])
                ->whereIn('vehicle_id', $vehicleIds)
                ->get();

        $operationsByDepartment = [
            'aquisition' => $operations->filter(fn($op) => optional($op->account_item->account_category)->account_department_id == 1)->values(),
            'garage' => $operations->filter(fn($op) => optional($op->account_item->account_category)->account_department_id == 2)->values(),
            'sale' => $operations->filter(fn($op) => optional($op->account_item->account_category)->account_department_id == 3)->values(),
        ];

        $timelogs = $vehicleIds->isEmpty()
            ? collect()
            : Timelog::with(['user', 'vehicle'])
                ->whereIn('vehicle_id', $vehicleIds)
                ->whereNotNull('rounded_minutes')
                ->orderBy('start_time')
                ->get();

        $hourPrice = 25;
        $financial = $this->calculateFinancialSummary($vehicleGroup, $operationsByDepartment, $timelogs, $hourPrice);
        $vehicleBreakdown = $this->buildVehicleBreakdown($vehicleGroup, $operations, $timelogs, $hourPrice);

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

    private function calculateFinancialSummary(VehicleGroup $vehicleGroup, array $operationsByDepartment, Collection $timelogs, int $hourPrice): array
    {
        $commissionTotal = (float) $vehicleGroup->vehicles->sum(fn($vehicle) => $vehicle->commission ?? 0);
        $purchasePrice = (float) $vehicleGroup->vehicles->sum(fn($vehicle) => $vehicle->purchase_price ?? 0) + $commissionTotal;
        $purchaseTotal = (float) ($operationsByDepartment['aquisition']->sum('total') ?? 0) + $commissionTotal;
        $purchaseBalance = $purchasePrice - $purchaseTotal;

        $garageTotal = (float) ($operationsByDepartment['garage']->sum('total') ?? 0);

        $finalSalesTarget = (float) $vehicleGroup->vehicles->sum(function ($vehicle) {
            return (float) ($vehicle->pvp ?? 0)
                + (float) ($vehicle->sales_iuc ?? 0)
                + (float) ($vehicle->sales_tow ?? 0)
                + (float) ($vehicle->sales_transfer ?? 0)
                + (float) ($vehicle->sales_others ?? 0);
        });

        $saleTotal = (float) ($operationsByDepartment['sale']->sum('total') ?? 0);
        $saleBalance = $finalSalesTarget - $saleTotal;

        $totalMinutes = (int) $timelogs->sum('rounded_minutes');
        $labourCost = ($totalMinutes / 60) * $hourPrice;

        $invested = $purchaseTotal + $garageTotal + $labourCost;
        $profit = $saleTotal - $invested;
        $roi = $invested > 0 ? ($profit / $invested) * 100 : 0;
        $theoreticalProfit = $finalSalesTarget - $invested;

        return [
            'purchasePrice' => $purchasePrice,
            'purchaseTotal' => $purchaseTotal,
            'purchaseBalance' => $purchaseBalance,
            'garageTotal' => $garageTotal,
            'finalSalesTarget' => $finalSalesTarget,
            'saleTotal' => $saleTotal,
            'saleBalance' => $saleBalance,
            'totalMinutes' => $totalMinutes,
            'labourCost' => $labourCost,
            'invested' => $invested,
            'profit' => $profit,
            'roi' => $roi,
            'theoreticalProfit' => $theoreticalProfit,
        ];
    }

    private function buildVehicleBreakdown(VehicleGroup $vehicleGroup, Collection $operations, Collection $timelogs, int $hourPrice): Collection
    {
        $operationsByVehicle = $operations->groupBy('vehicle_id');
        $timelogsByVehicle = $timelogs->groupBy('vehicle_id');

        return $vehicleGroup->vehicles->map(function ($vehicle) use ($operationsByVehicle, $timelogsByVehicle, $hourPrice) {
            $ops = $operationsByVehicle->get($vehicle->id, collect());

            $purchaseOps = $ops->filter(fn($op) => optional($op->account_item->account_category)->account_department_id == 1);
            $garageOps = $ops->filter(fn($op) => optional($op->account_item->account_category)->account_department_id == 2);
            $saleOps = $ops->filter(fn($op) => optional($op->account_item->account_category)->account_department_id == 3);

            $commission = (float) ($vehicle->commission ?? 0);
            $saleTarget = (float) ($vehicle->pvp ?? 0)
                + (float) ($vehicle->sales_iuc ?? 0)
                + (float) ($vehicle->sales_tow ?? 0)
                + (float) ($vehicle->sales_transfer ?? 0)
                + (float) ($vehicle->sales_others ?? 0);

            $vehicleTimelogs = $timelogsByVehicle->get($vehicle->id, collect());
            $minutes = (int) $vehicleTimelogs->sum('rounded_minutes');
            $labourCost = ($minutes / 60) * $hourPrice;

            $invested = (float) $purchaseOps->sum('total') + $commission + (float) $garageOps->sum('total') + $labourCost;
            $saleTotal = (float) $saleOps->sum('total');

            return [
                'vehicle' => $vehicle,
                'purchase_price' => (float) ($vehicle->purchase_price ?? 0) + $commission,
                'purchase_total' => (float) $purchaseOps->sum('total') + $commission,
                'garage_total' => (float) $garageOps->sum('total'),
                'sale_target' => $saleTarget,
                'sale_total' => $saleTotal,
                'sale_balance' => $saleTarget - $saleTotal,
                'minutes' => $minutes,
                'labour_cost' => $labourCost,
                'invested' => $invested,
                'profit' => $saleTotal - $invested,
            ];
        })
            ->sortBy(fn($row) => $row['vehicle']->license ?? $row['vehicle']->id)
            ->values();
    }
}
