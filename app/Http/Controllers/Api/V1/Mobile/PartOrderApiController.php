<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\Repair;
use App\Models\Suplier;
use App\Models\Vehicle;
use App\Services\PartOrderNotificationService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartOrderApiController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('part_order_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = PartOrder::with(['vehicle.brand', 'repair', 'suplier', 'items'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($vehicleQuery) use ($search) {
                    $vehicleQuery->where('license', 'like', '%' . $search . '%')
                        ->orWhere('foreign_license', 'like', '%' . $search . '%')
                        ->orWhere('model', 'like', '%' . $search . '%');
                })->orWhereHas('items', function ($itemQuery) use ($search) {
                    $itemQuery->where('description', 'like', '%' . $search . '%')
                        ->orWhere('reference', 'like', '%' . $search . '%');
                });
            });
        }

        $orders = $query->paginate(min(max((int) $request->query('per_page', 20), 1), 50));

        return response()->json([
            'data' => $orders->getCollection()->map(fn (PartOrder $order) => $this->payload($order)),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('part_order_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'repair_id' => ['nullable', 'integer', 'exists:repairs,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'suplier_id' => ['nullable', 'integer', 'exists:supliers,id'],
            'priority' => ['nullable', 'in:low,normal,urgent'],
            'requested_delivery_days' => ['nullable', 'integer', 'min:0'],
            'expected_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.reference' => ['nullable', 'string', 'max:191'],
            'items.*.description' => ['required', 'string', 'max:191'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.observations' => ['nullable', 'string'],
        ]);

        if (! empty($data['repair_id'])) {
            $repair = Repair::find($data['repair_id']);
            if ($repair && $repair->vehicle_id) {
                $data['vehicle_id'] = $repair->vehicle_id;
            }
        }

        $items = $data['items'];
        unset($data['items']);
        $data['requested_by_id'] = $request->user()->id;
        $data['technician_id'] = $request->user()->id;
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['status'] = 'draft';

        $order = PartOrder::create($data);
        foreach ($items as $item) {
            $order->items()->create([
                'reference' => $item['reference'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'status' => 'pending',
                'observations' => $item['observations'] ?? null,
            ]);
        }

        $order = $order->fresh(['vehicle.brand', 'repair', 'suplier', 'requested_by', 'technician', 'items']);
        app(PartOrderNotificationService::class)->sendCreated($order);

        return response()->json(['data' => $this->payload($order)], Response::HTTP_CREATED);
    }

    public function show(PartOrder $partOrder)
    {
        abort_if(Gate::denies('part_order_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'data' => $this->payload($partOrder->load(['vehicle.brand', 'repair', 'suplier', 'items.quotes.suplier', 'payments', 'receipts'])),
        ]);
    }

    public function storeItem(Request $request, PartOrder $partOrder)
    {
        abort_if(Gate::denies('part_order_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (in_array($partOrder->status, ['received', 'cancelled'], true)) {
            return response()->json(['message' => 'Encomenda fechada.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:191'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'observations' => ['nullable', 'string'],
        ]);

        $partOrder->items()->create([
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'],
            'quantity' => $data['quantity'] ?? 1,
            'observations' => $data['observations'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $this->payload($partOrder->fresh(['vehicle.brand', 'repair', 'suplier', 'items']))], Response::HTTP_CREATED);
    }

    public function updateItem(Request $request, PartOrder $partOrder, PartOrderItem $item)
    {
        abort_if(Gate::denies('part_order_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $item->part_order_id !== (int) $partOrder->id, Response::HTTP_NOT_FOUND);

        if (in_array($partOrder->status, ['received', 'cancelled'], true)) {
            return response()->json(['message' => 'Encomenda fechada.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:191'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'observations' => ['nullable', 'string'],
        ]);

        $item->update([
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'],
            'quantity' => $data['quantity'] ?? 1,
            'observations' => $data['observations'] ?? null,
        ]);

        return response()->json(['data' => $this->payload($partOrder->fresh(['vehicle.brand', 'repair', 'suplier', 'items']))]);
    }

    public function destroyItem(PartOrder $partOrder, PartOrderItem $item)
    {
        abort_if(Gate::denies('part_order_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $item->part_order_id !== (int) $partOrder->id, Response::HTTP_NOT_FOUND);

        if (in_array($partOrder->status, ['received', 'cancelled'], true)) {
            return response()->json(['message' => 'Encomenda fechada.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $item->delete();

        return response()->json(['data' => $this->payload($partOrder->fresh(['vehicle.brand', 'repair', 'suplier', 'items']))]);
    }

    public function suppliers()
    {
        abort_if(Gate::denies('part_order_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'data' => Suplier::query()
                ->where(function ($q) {
                    $q->where('active', true)->orWhereNull('active');
                })
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function storeSupplier(Request $request)
    {
        abort_if(Gate::denies('part_order_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:191'],
            'nif' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['active'] = true;

        $supplier = Suplier::create($data);

        return response()->json([
            'data' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
            ],
        ], Response::HTTP_CREATED);
    }

    private function payload(PartOrder $order): array
    {
        return [
            'id' => $order->id,
            'repair_id' => $order->repair_id,
            'vehicle_id' => $order->vehicle_id,
            'vehicle_label' => $this->vehicleLabel($order->vehicle),
            'supplier' => $order->suplier?->name,
            'priority' => $order->priority,
            'status' => $order->status,
            'status_label' => PartOrder::STATUS_SELECT[$order->status] ?? $order->status,
            'requested_delivery_days' => $order->requested_delivery_days,
            'expected_delivery_date' => optional($order->expected_delivery_date)->format('Y-m-d'),
            'actual_delivery_date' => optional($order->actual_delivery_date)->format('Y-m-d'),
            'notes' => $order->notes,
            'created_at' => optional($order->created_at)->format('Y-m-d H:i:s'),
            'items' => $order->items->map(fn (PartOrderItem $item) => [
                'id' => $item->id,
                'reference' => $item->reference,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'status' => $item->status,
                'observations' => $item->observations,
            ])->values(),
            'received_badge' => $this->receivedBadge($order),
        ];
    }

    private function vehicleLabel(?Vehicle $vehicle): ?string
    {
        if (! $vehicle) {
            return null;
        }

        return trim(implode(' ', array_filter([
            $vehicle->license ?: $vehicle->foreign_license,
            $vehicle->brand?->name,
            $vehicle->model,
        ]))) ?: 'Viatura #' . $vehicle->id;
    }

    private function receivedBadge(PartOrder $order): string
    {
        return match ($order->status) {
            'received' => 'chegou',
            'partially_received' => 'parcial',
            'delayed' => 'atrasado',
            default => 'pendente',
        };
    }
}
