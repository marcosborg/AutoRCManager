<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleConsignmentRequest;
use App\Http\Requests\UpdateVehicleConsignmentRequest;
use App\Models\OperationalUnit;
use App\Domain\Consignments\ConsignmentStatus;
use App\Models\Vehicle;
use App\Models\VehicleConsignment;
use App\Services\VehicleConsignmentService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class VehicleConsignmentController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_consignment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = VehicleConsignment::with(['vehicle', 'from_unit', 'to_unit'])
                ->when($request->get('vehicle_id'), function ($q, $vehicleId) {
                    $q->where('vehicle_id', $vehicleId);
                })
                ->select(sprintf('%s.*', (new VehicleConsignment)->table));

            $table = Datatables::of($query);
            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'vehicle_consignment_show';
                $editGate = 'vehicle_consignment_edit';
                $crudRoutePart = 'vehicle-consignments';

                return view('partials.consignmentActions', compact(
                    'viewGate',
                    'editGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', fn($row) => $row->id ?? '');
            $table->addColumn('vehicle_label', fn($row) => $this->vehicleLabel($row->vehicle));
            $table->addColumn('from_unit_name', fn($row) => optional($row->from_unit)->name ?? '');
            $table->addColumn('to_unit_name', fn($row) => optional($row->to_unit)->name ?? '');
            $table->editColumn('reference_value', fn($row) => $row->reference_value ?? '');
            $table->editColumn('starts_at', fn($row) => $row->starts_at ?? '');
            $table->editColumn('ends_at', fn($row) => $row->ends_at ?? '');
            $table->editColumn('status', fn($row) => $row->status ?? '');

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        $vehicles = $this->vehicleOptions();

        return view('admin.vehicleConsignments.index', compact('vehicles'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_consignment_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = $this->vehicleOptions();
        $units = $this->operationalUnitOptions();

        return view('admin.vehicleConsignments.create', compact('vehicles', 'units'));
    }

    public function store(StoreVehicleConsignmentRequest $request, VehicleConsignmentService $service)
    {
        $payload = $request->validated();

        $consignment = $service->createConsignment($payload);

        return redirect()->route('admin.vehicle-consignments.show', $consignment->id)
            ->with('message', 'Consignacao criada com sucesso');
    }

    public function edit(VehicleConsignment $vehicleConsignment)
    {
        abort_if(Gate::denies('vehicle_consignment_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleConsignment->load(['vehicle', 'from_unit', 'to_unit']);

        return view('admin.vehicleConsignments.edit', compact('vehicleConsignment'));
    }

    public function update(UpdateVehicleConsignmentRequest $request, VehicleConsignment $vehicleConsignment, VehicleConsignmentService $service)
    {
        $payload = $request->validated();
        $payload['status'] = ConsignmentStatus::CLOSED;

        $service->closeConsignment($vehicleConsignment, $payload);

        return redirect()->route('admin.vehicle-consignments.show', $vehicleConsignment->id)
            ->with('message', 'Consignacao encerrada com sucesso');
    }

    public function show(VehicleConsignment $vehicleConsignment)
    {
        abort_if(Gate::denies('vehicle_consignment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleConsignment->load(['vehicle', 'from_unit', 'to_unit']);

        return view('admin.vehicleConsignments.show', compact('vehicleConsignment'));
    }

    private function vehicleOptions()
    {
        return Vehicle::with('brand')->get()->mapWithKeys(function ($vehicle) {
            return [$vehicle->id => $this->vehicleLabel($vehicle)];
        });
    }

    private function vehicleLabel(?Vehicle $vehicle): string
    {
        if (! $vehicle) {
            return '';
        }

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

        return $label !== '' ? $label : 'Veiculo #' . $vehicle->id;
    }

    private function operationalUnitOptions()
    {
        return OperationalUnit::orderBy('name')
            ->get()
            ->mapWithKeys(function ($unit) {
                $label = $unit->code ? sprintf('%s (%s)', $unit->name, $unit->code) : $unit->name;

                return [$unit->id => $label];
            });
    }
}
