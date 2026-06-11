<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartOrderRequest;
use App\Http\Requests\UpdatePartOrderRequest;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\PartQuote;
use App\Models\Repair;
use App\Models\Suplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PartOrderNotificationService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartOrderController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('part_order_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = PartOrder::with(['repair', 'vehicle.brand', 'suplier', 'requested_by', 'technician'])
            ->withCount('items')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('suplier_id')) {
            $query->where('suplier_id', $request->suplier_id);
        }

        if ($request->filled('vehicle_search')) {
            $search = trim((string) $request->vehicle_search);
            $query->whereHas('vehicle', function ($vehicleQuery) use ($search) {
                $vehicleQuery->where(function ($vehicleSearch) use ($search) {
                    $vehicleSearch->searchByLicense($search)
                        ->orWhere('model', 'like', '%' . $search . '%');
                });
            });
        }

        if ($request->boolean('delayed')) {
            $query->where(function ($q) {
                $q->where('status', 'delayed')
                    ->orWhere(function ($sub) {
                        $sub->whereDate('expected_delivery_date', '<', now()->toDateString())
                            ->whereNull('actual_delivery_date')
                            ->whereNotIn('status', ['received', 'cancelled']);
                    });
            });
        }

        $partOrders = $query->paginate(50)->appends($request->query());
        $supliers = Suplier::orderBy('name')->pluck('name', 'id');

        return view('admin.partOrders.index', compact('partOrders', 'supliers'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('part_order_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.partOrders.create', $this->formData(null, $request));
    }

    public function store(StorePartOrderRequest $request)
    {
        $data = $this->orderData($request);
        $partOrder = PartOrder::create($data);
        $this->syncItems($partOrder, $request->input('items', []));
        app(PartOrderNotificationService::class)->sendCreated($partOrder->fresh(['vehicle.brand', 'repair', 'suplier', 'requested_by', 'technician', 'items']));

        return redirect()->route('admin.part-orders.edit', $partOrder)->with('message', 'Encomenda criada com sucesso.');
    }

    public function edit(PartOrder $partOrder)
    {
        abort_if(Gate::denies('part_order_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partOrder->load(['items.quotes.suplier', 'repair.vehicle.brand', 'vehicle.brand', 'suplier', 'payments', 'receipts.received_by']);

        return view('admin.partOrders.edit', $this->formData($partOrder));
    }

    public function update(UpdatePartOrderRequest $request, PartOrder $partOrder)
    {
        $partOrder->update($this->orderData($request));
        $this->syncItems($partOrder, $request->input('items', []));
        $partOrder->refreshReceiptStatus();

        return redirect()->route('admin.part-orders.edit', $partOrder)->with('message', 'Encomenda atualizada com sucesso.');
    }

    public function show(PartOrder $partOrder)
    {
        abort_if(Gate::denies('part_order_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partOrder->load(['items.quotes.suplier', 'repair.vehicle.brand', 'vehicle.brand', 'suplier', 'requested_by', 'technician', 'payments.suplier', 'receipts.received_by']);

        return view('admin.partOrders.show', compact('partOrder'));
    }

    public function destroy(PartOrder $partOrder)
    {
        abort_if(Gate::denies('part_order_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partOrder->delete();

        return redirect()->route('admin.part-orders.index');
    }

    public function storeQuote(Request $request, PartOrder $partOrder, PartOrderItem $item)
    {
        abort_if(Gate::denies('part_order_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $item->part_order_id !== (int) $partOrder->id, Response::HTTP_NOT_FOUND);

        $data = $request->validate([
            'suplier_id' => ['required', 'integer', 'exists:supliers,id'],
            'quoted_price' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery_days' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'requested_at' => ['nullable', 'date'],
            'responded_at' => ['nullable', 'date'],
        ]);

        $item->quotes()->create($data);

        return redirect()->route('admin.part-orders.edit', $partOrder)->with('message', 'Cotacao adicionada.');
    }

    public function selectQuote(PartOrder $partOrder, PartOrderItem $item, PartQuote $quote)
    {
        abort_if(Gate::denies('part_order_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $item->part_order_id !== (int) $partOrder->id || (int) $quote->part_order_item_id !== (int) $item->id, Response::HTTP_NOT_FOUND);

        $item->quotes()->update(['selected' => false]);
        $quote->update(['selected' => true]);

        $item->unit_price_final = $quote->quoted_price;
        $item->calculateTotals();
        $item->save();

        if (! $partOrder->suplier_id) {
            $partOrder->suplier_id = $quote->suplier_id;
            $partOrder->save();
        }

        return redirect()->route('admin.part-orders.edit', $partOrder)->with('message', 'Cotacao selecionada.');
    }

    private function formData(?PartOrder $partOrder = null, ?Request $request = null): array
    {
        $repairs = Repair::with('vehicle.brand')->orderByDesc('id')->limit(500)->get()->mapWithKeys(function (Repair $repair) {
            $vehicle = $repair->vehicle;
            $label = '#' . $repair->id . ' - ' . trim(implode(' ', array_filter([
                $vehicle?->license ?: $vehicle?->foreign_license,
                $vehicle?->brand?->name,
                $vehicle?->model,
            ])));
            return [$repair->id => $label];
        });

        return [
            'partOrder' => $partOrder,
            'repairs' => $repairs,
            'vehicles' => Vehicle::with('brand')->orderByDesc('id')->limit(500)->get()->mapWithKeys(fn (Vehicle $vehicle) => [
                $vehicle->id => trim(implode(' ', array_filter([$vehicle->license ?: $vehicle->foreign_license, $vehicle->brand?->name, $vehicle->model]))) ?: 'Viatura #' . $vehicle->id,
            ]),
            'supliers' => Suplier::orderBy('name')->pluck('name', 'id'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'selectedRepairId' => $request?->integer('repair_id') ?: $partOrder?->repair_id,
            'selectedVehicleId' => $request?->integer('vehicle_id') ?: $partOrder?->vehicle_id,
        ];
    }

    private function orderData(Request $request): array
    {
        $data = $request->only([
            'repair_id',
            'vehicle_id',
            'requested_by_id',
            'technician_id',
            'suplier_id',
            'priority',
            'status',
            'requested_delivery_days',
            'expected_delivery_date',
            'actual_delivery_date',
            'notes',
        ]);

        if (! empty($data['repair_id'])) {
            $repair = Repair::find($data['repair_id']);
            if ($repair && $repair->vehicle_id) {
                $data['vehicle_id'] = $repair->vehicle_id;
            }
        }

        return $data;
    }

    private function syncItems(PartOrder $partOrder, array $items): void
    {
        $keptIds = [];

        foreach ($items as $itemData) {
            if (empty($itemData['description'])) {
                continue;
            }

            $item = ! empty($itemData['id'])
                ? $partOrder->items()->whereKey($itemData['id'])->first()
                : new PartOrderItem(['part_order_id' => $partOrder->id]);

            if (! $item) {
                continue;
            }

            $item->fill([
                'reference' => $itemData['reference'] ?? null,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'] ?? 1,
                'iva_percentage' => $itemData['iva_percentage'] ?? null,
                'status' => $itemData['status'] ?? 'pending',
                'observations' => $itemData['observations'] ?? null,
            ]);
            $item->calculateTotals();
            $item->save();
            $keptIds[] = $item->id;
        }

        $partOrder->items()->whereNotIn('id', $keptIds ?: [0])->delete();
    }
}
