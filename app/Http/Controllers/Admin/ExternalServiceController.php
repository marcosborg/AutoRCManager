<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExternalServiceRequest;
use App\Http\Requests\UpdateExternalServiceRequest;
use App\Models\ExternalService;
use App\Models\Suplier;
use App\Models\User;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExternalServiceController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('external_service_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = ExternalService::with(['vehicle', 'suplier', 'requested_by', 'media'])->latest();
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('suplier_id'), fn ($q) => $q->where('suplier_id', $request->integer('suplier_id')));
        $query->when($request->filled('vehicle_search'), function ($q) use ($request) {
            $search = trim((string) $request->vehicle_search);
            $q->whereHas('vehicle', fn ($vehicle) => $vehicle->where(fn ($sub) => $sub->searchByLicense($search)->orWhere('model', 'like', '%'.$search.'%')));
        });

        return view('admin.externalServices.index', [
            'externalServices' => $query->paginate(30)->withQueryString(),
            'supliers' => Suplier::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('external_service_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.externalServices.create', $this->formData(null, $request));
    }

    public function store(StoreExternalServiceRequest $request)
    {
        $externalService = ExternalService::create($this->serviceData($request));
        $this->syncInvoice($request, $externalService);

        return redirect()->route('admin.external-services.index')->with('message', 'Serviço externo criado com sucesso.');
    }

    public function edit(ExternalService $externalService)
    {
        abort_if(Gate::denies('external_service_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.externalServices.edit', $this->formData($externalService));
    }

    public function update(UpdateExternalServiceRequest $request, ExternalService $externalService)
    {
        $externalService->update($this->serviceData($request));
        $this->syncInvoice($request, $externalService);

        return redirect()->route('admin.external-services.index')->with('message', 'Serviço externo atualizado com sucesso.');
    }

    public function destroy(ExternalService $externalService)
    {
        abort_if(Gate::denies('external_service_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $externalService->delete();

        return back();
    }

    private function formData(?ExternalService $externalService = null, ?Request $request = null): array
    {
        return [
            'externalService' => $externalService,
            'vehicles' => Vehicle::latest()->limit(500)->get()->mapWithKeys(fn (Vehicle $vehicle) => [$vehicle->id => $vehicle->license ?: $vehicle->foreign_license ?: '#'.$vehicle->id]),
            'supliers' => Suplier::orderBy('name')->pluck('name', 'id'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'selectedVehicleId' => $request?->integer('vehicle_id') ?: $externalService?->vehicle_id,
        ];
    }

    private function serviceData(Request $request): array
    {
        $data = $request->only(['vehicle_id', 'suplier_id', 'requested_by_id', 'description', 'priority', 'status', 'requested_delivery_days', 'expected_date', 'completed_date', 'amount', 'notes']);
        if ($data['status'] === 'completed' && empty($data['completed_date'])) {
            $data['completed_date'] = now()->toDateString();
        }

        return $data;
    }

    private function syncInvoice(Request $request, ExternalService $externalService): void
    {
        if (! $request->hasFile('invoice_file')) {
            return;
        }

        $externalService->clearMediaCollection('invoice_file');
        $externalService->addMediaFromRequest('invoice_file')->toMediaCollection('invoice_file');
    }
}
