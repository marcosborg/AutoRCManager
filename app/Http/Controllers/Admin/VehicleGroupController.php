<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyVehicleGroupRequest;
use App\Http\Requests\StoreVehicleGroupRequest;
use App\Http\Requests\UpdateVehicleGroupRequest;
use App\Models\Client;
use App\Models\LotPayment;
use App\Models\PaymentMethod;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use App\Services\VehicleLotService;
use Gate;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleGroupController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('vehicle_group_access') && Gate::denies('vehicle_lot_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleGroups = VehicleGroup::with(['customer'])
            ->withCount(['items', 'payments'])
            ->latest()
            ->get();

        return view('admin.vehicleGroups.index', compact('vehicleGroups'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_group_create') && Gate::denies('vehicle_lot_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = $this->vehicleOptions();
        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicleGroups.create', compact('vehicles', 'clients'));
    }

    public function store(StoreVehicleGroupRequest $request, VehicleLotService $service)
    {
        $payload = $this->lotPayload($request->validated());
        $vehicleGroup = VehicleGroup::create($payload);

        $this->syncLegacyClients($vehicleGroup, $request);
        $service->syncLotItems($vehicleGroup, $request->input('vehicles', []), $request->input('items', []));

        return redirect()->route('admin.vehicle-groups.show', $vehicleGroup->id)->with('message', 'Lote criado com sucesso');
    }

    public function edit(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_group_edit') && Gate::denies('vehicle_lot_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = $this->vehicleOptions();
        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicleGroup->load('items.vehicle.brand', 'vehicles', 'clients');

        return view('admin.vehicleGroups.edit', compact('vehicleGroup', 'vehicles', 'clients'));
    }

    public function update(UpdateVehicleGroupRequest $request, VehicleGroup $vehicleGroup, VehicleLotService $service)
    {
        $payload = $this->lotPayload($request->validated());
        $payload['approved_by'] = null;
        $payload['approved_at'] = null;
        $vehicleGroup->update($payload);

        $this->syncLegacyClients($vehicleGroup, $request);
        $service->syncLotItems($vehicleGroup, $request->input('vehicles', []), $request->input('items', []));

        return redirect()->route('admin.vehicle-groups.show', $vehicleGroup->id)->with('message', 'Lote atualizado com sucesso');
    }

    public function show(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_group_show') && Gate::denies('vehicle_lot_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        app(VehicleLotService::class)->recalculate($vehicleGroup);

        $vehicleGroup->load([
            'customer',
            'clients',
            'items.vehicle.brand',
            'items.vehicle.general_state',
            'payments.payment_method',
            'payments.creator',
            'payments.confirmer',
            'payments.rejecter',
            'approver',
        ]);

        $paymentMethods = PaymentMethod::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $canApproveLots = Gate::allows('vehicle_lot_approve');
        $canCreateLotPayments = Gate::allows('vehicle_lot_payment_create') || Gate::allows('vehicle_group_edit');

        $financial = [
            'target' => (float) $vehicleGroup->items->sum(fn ($item) => $item->sale_target),
            'paid' => (float) $vehicleGroup->items->sum('paid_amount'),
            'invoiced' => (float) $vehicleGroup->items->sum('invoiced_amount'),
            'cash' => (float) $vehicleGroup->items->sum('cash_amount'),
            'pending' => (float) $vehicleGroup->payments->where('approval_status', LotPayment::STATUS_PENDING)->sum('amount'),
        ];
        $financial['balance'] = max(0, $financial['target'] - $financial['paid']);

        return view('admin.vehicleGroups.show', compact(
            'vehicleGroup',
            'financial',
            'paymentMethods',
            'canApproveLots',
            'canCreateLotPayments'
        ));
    }

    public function destroy(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_group_delete') && Gate::denies('vehicle_lot_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

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

    public function storePayment(Request $request, VehicleGroup $vehicleGroup, VehicleLotService $service)
    {
        abort_if(Gate::denies('vehicle_lot_payment_create') && Gate::denies('vehicle_group_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'paid_at' => ['required', 'date_format:' . config('panel.date_format')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'invoiced_amount' => ['required', 'numeric', 'min:0'],
            'cash_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'max:10240'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $split = round((float) $data['invoiced_amount'] + (float) $data['cash_amount'], 2);
        if (abs($amount - $split) > 0.01) {
            return back()->withErrors(['amount' => 'O valor recebido tem de ser igual a faturado + caixa.'])->withInput();
        }

        $methodName = optional(PaymentMethod::find($data['payment_method_id']))->name;
        $isCash = $methodName && str_contains(strtolower($methodName), 'dinheiro');
        if (! $request->hasFile('proof_file') && ! $isCash) {
            return back()->withErrors(['proof_file' => 'Comprovativo obrigatorio para pagamentos que nao sejam dinheiro.'])->withInput();
        }
        if ($isCash && empty(trim((string) ($data['notes'] ?? '')))) {
            return back()->withErrors(['notes' => 'Pagamentos em dinheiro exigem nota curta.'])->withInput();
        }

        $payment = LotPayment::create([
            'vehicle_group_id' => $vehicleGroup->id,
            'payment_method_id' => $data['payment_method_id'],
            'paid_at' => $data['paid_at'],
            'amount' => $data['amount'],
            'invoiced_amount' => $data['invoiced_amount'],
            'cash_amount' => $data['cash_amount'],
            'approval_status' => LotPayment::STATUS_PENDING,
            'created_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->hasFile('proof_file')) {
            $payment->addMediaFromRequest('proof_file')->toMediaCollection('proof_file');
        }

        $service->recalculate($vehicleGroup);

        return redirect()->route('admin.vehicle-groups.show', $vehicleGroup->id)->with('message', 'Pagamento submetido para aprovacao.');
    }

    public function approveLot(VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleGroup->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('message', 'Lote aprovado com sucesso.');
    }

    public function approvePayment(VehicleGroup $vehicleGroup, LotPayment $payment, VehicleLotService $service)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $payment->vehicle_group_id !== (int) $vehicleGroup->id, Response::HTTP_NOT_FOUND);

        $service->approvePayment($payment, auth()->id());

        return back()->with('message', 'Pagamento aprovado com sucesso.');
    }

    public function rejectPayment(Request $request, VehicleGroup $vehicleGroup, LotPayment $payment, VehicleLotService $service)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $payment->vehicle_group_id !== (int) $vehicleGroup->id, Response::HTTP_NOT_FOUND);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->rejectPayment($payment, auth()->id(), $data['rejection_reason']);

        return back()->with('message', 'Pagamento rejeitado.');
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

    private function lotPayload(array $validated): array
    {
        $total = $validated['total_amount'] ?? $validated['wholesale_pvp'] ?? null;

        return [
            'customer_id' => $validated['customer_id'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'] ?? 'lote',
            'wholesale_pvp' => $total,
            'total_amount' => $total,
            'distribution_mode' => $validated['distribution_mode'] ?? 'proportional',
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function syncLegacyClients(VehicleGroup $vehicleGroup, Request $request): void
    {
        $clientIds = $request->input('clients', []);
        if ($request->filled('customer_id')) {
            $clientIds[] = (int) $request->input('customer_id');
        }

        $vehicleGroup->clients()->sync(array_values(array_unique(array_filter($clientIds))));
    }
}
