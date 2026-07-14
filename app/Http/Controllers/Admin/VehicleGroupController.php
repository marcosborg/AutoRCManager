<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyVehicleGroupRequest;
use App\Http\Requests\StoreVehicleGroupPaymentRequest;
use App\Http\Requests\StoreVehicleGroupRequest;
use App\Http\Requests\UpdateVehicleGroupRequest;
use App\Models\Brand;
use App\Models\Client;
use App\Models\LotPayment;
use App\Models\PaymentMethod;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use App\Services\VehicleLotService;
use App\Services\VehicleTradeInPaymentService;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

        return view('admin.vehicleGroups.edit', [
            'vehicleGroup' => $vehicleGroup,
            'vehicles' => $vehicles,
            'clients' => $clients,
            ...$this->paymentViewData($vehicleGroup),
        ]);
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

        return view('admin.vehicleGroups.show', [
            'vehicleGroup' => $vehicleGroup,
            ...$this->paymentViewData($vehicleGroup),
        ]);
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

    public function storePayment(
        StoreVehicleGroupPaymentRequest $request,
        VehicleGroup $vehicleGroup,
        VehicleLotService $service,
        VehicleTradeInPaymentService $tradeInService
    ) {
        $data = $request->validated();

        $isTradeInPayment = $data['payment_type'] === 'trade_in';
        $paymentMethod = $isTradeInPayment
            ? PaymentMethod::query()->whereRaw('LOWER(name) = ?', ['retoma'])->first()
            : PaymentMethod::find($data['payment_method_id'] ?? null);

        if (! $paymentMethod) {
            throw ValidationException::withMessages([
                'payment_method_id' => $isTradeInPayment
                    ? 'O metodo de pagamento Retoma nao esta configurado.'
                    : 'Selecione o metodo de pagamento.',
            ]);
        }

        if ($isTradeInPayment) {
            if (! $vehicleGroup->customer) {
                throw ValidationException::withMessages([
                    'payment_type' => 'Associe um cliente ao lote antes de registar uma retoma.',
                ]);
            }

            $tradeInService->validate($data);
        } else {
            $normalizedMethod = Str::lower(Str::ascii($paymentMethod->name));
            $isCash = Str::contains($normalizedMethod, ['numerario', 'dinheiro']);

            if (! $request->hasFile('proof_file') && ! $isCash) {
                throw ValidationException::withMessages([
                    'proof_file' => 'Comprovativo obrigatorio para este metodo de pagamento.',
                ]);
            }

            if ($isCash && empty(trim((string) ($data['notes'] ?? '')))) {
                throw ValidationException::withMessages([
                    'notes' => 'Pagamentos em numerario exigem uma nota curta.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $vehicleGroup, $paymentMethod, $data, $isTradeInPayment, $tradeInService): void {
            $tradeIn = $isTradeInPayment
                ? $tradeInService->create($vehicleGroup->customer, $data, $request->user()?->id)
                : null;

            $payment = LotPayment::create([
                'vehicle_group_id' => $vehicleGroup->id,
                'payment_method_id' => $paymentMethod->id,
                'vehicle_trade_in_id' => $tradeIn?->id,
                'paid_at' => $data['paid_at'],
                'amount' => $data['amount'],
                'invoiced_amount' => $data['invoiced_amount'] ?? 0,
                'bank_amount' => $data['bank_amount'] ?? 0,
                'cash_amount' => $data['cash_amount'] ?? 0,
                'cash_2_amount' => $data['cash_2_amount'] ?? 0,
                'approval_status' => LotPayment::STATUS_PENDING,
                'created_by' => $request->user()?->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($request->hasFile('proof_file')) {
                $payment->addMediaFromRequest('proof_file')->toMediaCollection('proof_file');
            }
        });

        $service->recalculate($vehicleGroup);

        return $this->paymentActionRedirect($request, $vehicleGroup)
            ->with('message', 'Pagamento submetido para aprovacao.');
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

    public function approvePayment(Request $request, VehicleGroup $vehicleGroup, LotPayment $payment, VehicleLotService $service)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $payment->vehicle_group_id !== (int) $vehicleGroup->id, Response::HTTP_NOT_FOUND);

        $service->approvePayment($payment, auth()->id());

        return $this->paymentActionRedirect($request, $vehicleGroup)
            ->with('message', 'Pagamento aprovado com sucesso.');
    }

    public function rejectPayment(Request $request, VehicleGroup $vehicleGroup, LotPayment $payment, VehicleLotService $service)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $payment->vehicle_group_id !== (int) $vehicleGroup->id, Response::HTTP_NOT_FOUND);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
            'return_to' => ['nullable', 'in:show,edit'],
        ]);

        $service->rejectPayment($payment, auth()->id(), $data['rejection_reason']);

        return $this->paymentActionRedirect($request, $vehicleGroup)
            ->with('message', 'Pagamento rejeitado.');
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

            return [$vehicle->id => $label !== '' ? $label : 'Veiculo #'.$vehicle->id];
        });
    }

    private function lotPayload(array $validated): array
    {
        $total = $validated['total_amount'] ?? $validated['wholesale_pvp'] ?? 0;

        return [
            'customer_id' => $validated['customer_id'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'] ?? 'lote',
            'wholesale_pvp' => $total,
            'total_amount' => $total,
            'distribution_mode' => 'global',
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

    /**
     * @return array{
     *     financial: array{target: float, paid: float, invoiced: float, bank: float, cash: float, cash_2: float, pending: float, balance: float},
     *     paymentMethods: Collection,
     *     brands: Collection,
     *     canApproveLots: bool,
     *     canCreateLotPayments: bool
     * }
     */
    private function paymentViewData(VehicleGroup $vehicleGroup): array
    {
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
            'payments.vehicle_trade_in.created_vehicle',
            'approver',
        ]);

        $financial = [
            'target' => (float) $vehicleGroup->effective_total,
            'paid' => (float) $vehicleGroup->approved_paid_total,
            'invoiced' => (float) $vehicleGroup->approved_invoiced_total,
            'bank' => (float) $vehicleGroup->approved_bank_total,
            'cash' => (float) $vehicleGroup->approved_cash_total,
            'cash_2' => (float) $vehicleGroup->approved_cash_2_total,
            'pending' => (float) $vehicleGroup->payments->where('approval_status', LotPayment::STATUS_PENDING)->sum('amount'),
        ];
        $financial['balance'] = max(0, $financial['target'] - $financial['paid']);

        return [
            'financial' => $financial,
            'paymentMethods' => PaymentMethod::query()
                ->whereRaw('LOWER(name) <> ?', ['retoma'])
                ->pluck('name', 'id')
                ->prepend(trans('global.pleaseSelect'), ''),
            'brands' => Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), ''),
            'canApproveLots' => Gate::allows('vehicle_lot_approve'),
            'canCreateLotPayments' => Gate::allows('vehicle_lot_payment_create') || Gate::allows('vehicle_group_edit'),
        ];
    }

    private function paymentActionRedirect(Request $request, VehicleGroup $vehicleGroup): RedirectResponse
    {
        return match ($request->input('return_to')) {
            'edit' => redirect()->to(route('admin.vehicle-groups.edit', $vehicleGroup).'#lot-payments'),
            'show' => redirect()->route('admin.vehicle-groups.show', $vehicleGroup),
            default => redirect()->back(),
        };
    }
}
