<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyVehicleFinancialEntryRequest;
use App\Http\Requests\StoreVehicleFinancialEntryRequest;
use App\Http\Requests\UpdateVehicleFinancialEntryRequest;
use App\Models\Vehicle;
use App\Models\VehicleFinancialEntry;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleFinancialEntryController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('vehicle_financial_entry_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleFinancialEntries = VehicleFinancialEntry::with('vehicle')
            ->orderBy('entry_date', 'desc')
            ->get();

        return view('admin.vehicleFinancialEntries.index', compact('vehicleFinancialEntries'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('vehicle_financial_entry_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');
        $entryTypes = [
            'cost' => 'Custo',
            'revenue' => 'Receita',
        ];
        $selectedVehicleId = $request->query('vehicle_id');

        return view('admin.vehicleFinancialEntries.create', compact('vehicles', 'entryTypes', 'selectedVehicleId'));
    }

    public function store(StoreVehicleFinancialEntryRequest $request)
    {
        VehicleFinancialEntry::create($request->all());

        return redirect()->route('admin.vehicle-financial-entries.index');
    }

    public function edit(VehicleFinancialEntry $vehicleFinancialEntry)
    {
        abort_if(Gate::denies('vehicle_financial_entry_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');
        $entryTypes = [
            'cost' => 'Custo',
            'revenue' => 'Receita',
        ];

        $vehicleFinancialEntry->load('vehicle');

        return view('admin.vehicleFinancialEntries.edit', compact('vehicleFinancialEntry', 'vehicles', 'entryTypes'));
    }

    public function update(UpdateVehicleFinancialEntryRequest $request, VehicleFinancialEntry $vehicleFinancialEntry)
    {
        $vehicleFinancialEntry->update($request->all());

        return redirect()->route('admin.vehicle-financial-entries.index');
    }

    public function show(VehicleFinancialEntry $vehicleFinancialEntry)
    {
        abort_if(Gate::denies('vehicle_financial_entry_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleFinancialEntry->load('vehicle');

        return view('admin.vehicleFinancialEntries.show', compact('vehicleFinancialEntry'));
    }

    public function destroy(VehicleFinancialEntry $vehicleFinancialEntry)
    {
        abort_if(Gate::denies('vehicle_financial_entry_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleFinancialEntry->delete();

        return back();
    }

    public function massDestroy(MassDestroyVehicleFinancialEntryRequest $request)
    {
        $entries = VehicleFinancialEntry::find(request('ids'));

        foreach ($entries as $entry) {
            $entry->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
