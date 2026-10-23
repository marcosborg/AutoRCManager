<?php
namespace App\Services;

use App\Models\{Vehicle, Brand, WorkshopState, Repair};
use App\Support\WorkshopPrivacy;
use Illuminate\Http\Request;

class WorkshopVehicleView
{
    public function index(Request $request)
    {
        $term = trim((string) $request->input('q', $request->input('search.value', '')));
        $vehicles = Vehicle::query()->select(WorkshopPrivacy::VEHICLE_FIELDS)->with(['brand:id,name', 'workshop_state:id,name', 'general_state:id,name'])
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->searchByLicense($term)->orWhere('model', 'like', '%'.$term.'%')))
            ->orderByDesc('id')->paginate(30)->withQueryString();
        if ($request->ajax()) return response()->json(['data' => $vehicles->items(), 'recordsTotal' => $vehicles->total(), 'recordsFiltered' => $vehicles->total()]);
        return view('admin.vehicles.workshop-index', compact('vehicles', 'term'));
    }

    public function show(Vehicle $vehicle, bool $editing)
    {
        $vehicle->load(['brand', 'workshop_state']);
        $context = app(VehicleContextService::class)->forVehicle($vehicle);
        $context = array_intersect_key($context, array_flip(['general_state', 'workshop_state', 'location', 'storage_location']));
        $data = $vehicle->only(WorkshopPrivacy::VEHICLE_FIELDS);
        $brands = Brand::orderBy('name')->pluck('name', 'id');
        $workshopStates = WorkshopState::where('is_active', true)->orWhere('id', $vehicle->workshop_state_id)->orderBy('name')->pluck('name', 'id');
        $repairs = Repair::where('vehicle_id', $vehicle->id)->orderByDesc('id')->get(['id', 'vehicle_id', 'work_type']);
        return view('admin.vehicles.workshop-details', compact('data', 'editing', 'context', 'brands', 'workshopStates', 'repairs'));
    }
}
