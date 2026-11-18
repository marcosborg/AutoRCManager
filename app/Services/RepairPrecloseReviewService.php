<?php

namespace App\Services;

use App\Models\ExternalService;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\Repair;
use App\Models\WorkshopIntervention;
use Illuminate\Support\Facades\Gate;

/** Read-only checklist. It never decides whether a repair can be finished. */
class RepairPrecloseReviewService
{
    public function build(Repair $repair): array
    {
        $repair->loadMissing(['workLogs.user', 'workLogs.workshopIntervention', 'workshopInterventions', 'parts']);
        $tasks = $repair->workshopInterventions->map(fn ($task) => [
            'id' => $task->id,
            'label' => $task->title ?: 'Tarefa por identificar',
            'status' => WorkshopIntervention::STATUS_SELECT[$task->status] ?? 'Estado por confirmar',
            'pending' => ! in_array($task->status, ['completed', 'cancelled'], true),
            'url' => $this->link('workshop_planning_edit', 'admin.workshop-interventions.edit', $task->id),
        ]);
        $parts = $repair->parts->map(function ($part) {
            $missing = [];
            if ($part->amount === null) {
                $missing[] = 'Valor por confirmar';
            }
            if (trim((string) $part->invoice_number) === '') {
                $missing[] = 'Número de fatura por confirmar';
            }

            return ['id' => $part->id, 'label' => $part->part_name ?: 'Peça por identificar', 'missing' => $missing];
        })->filter(fn ($row) => count($row['missing']) > 0)->values()->all();

        $orders = ['allowed' => Gate::allows('part_order_access'), 'repair' => [], 'vehicle' => []];
        if ($orders['allowed']) {
            $records = PartOrder::with('items')->where(function ($query) use ($repair) {
                $query->where('repair_id', $repair->id);
                if ($repair->vehicle_id) {
                    $query->orWhere(function ($query) use ($repair) {
                        $query->whereNull('repair_id')->where('vehicle_id', $repair->vehicle_id);
                    });
                }
            })->orderBy('id')->get()->unique('id');
            foreach ($records as $order) {
                $cancelled = $order->status === 'cancelled';
                $items = $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->description ?: 'Peça por identificar',
                    'status' => PartOrderItem::STATUS_SELECT[$item->status] ?? 'Estado por confirmar',
                    'quantity' => $item->quantity === null ? null : (float) $item->quantity,
                    'received' => $item->quantity === null ? null : $item->receivedAmount(),
                    'pending' => ! $cancelled && ! in_array($item->status, ['received', 'installed', 'returned'], true),
                ])->values()->all();
                $row = [
                    'id' => $order->id,
                    'label' => 'Encomenda #'.$order->id,
                    'status' => PartOrder::STATUS_SELECT[$order->status] ?? 'Estado por confirmar',
                    'pending' => ! $cancelled && (collect($items)->contains('pending', true)
                        || (! count($items) && $order->status !== 'received')),
                    'items' => $items,
                    'url' => $this->link('part_order_show', 'admin.part-orders.show', $order->id)
                        ?? $this->link('part_order_edit', 'admin.part-orders.edit', $order->id),
                ];
                $orders[(int) $order->repair_id === (int) $repair->id ? 'repair' : 'vehicle'][] = $row;
            }
        }

        $services = ['allowed' => Gate::allows('external_service_access'), 'rows' => []];
        if ($services['allowed'] && $repair->vehicle_id) {
            $services['rows'] = ExternalService::with('media')->where('vehicle_id', $repair->vehicle_id)
                ->orderBy('id')->get()->map(function ($service) {
                    $cancelled = $service->status === 'cancelled';
                    $hasInvoice = $service->invoice_file->isNotEmpty();

                    return [
                        'id' => $service->id,
                        'label' => $service->description ?: 'Serviço por identificar',
                        'status' => ExternalService::STATUS_SELECT[$service->status] ?? 'Estado por confirmar',
                        'pending' => ! $cancelled && ($service->status !== 'completed' || ! $hasInvoice),
                        'invoice' => $hasInvoice ? 'Fatura anexada' : ($cancelled
                            ? 'Sem fatura anexada — serviço cancelado.' : 'Fatura por confirmar'),
                        'url' => $this->link('external_service_edit', 'admin.external-services.edit', $service->id),
                    ];
                })->all();
        }

        return [
            'time' => app(RepairTimeReviewService::class)->build($repair->workLogs)['totals'],
            'tasks' => [
                'pending' => $tasks->where('pending', true)->values()->all(),
                'other' => $tasks->where('pending', false)->values()->all(),
            ],
            'parts' => ['total_count' => $repair->parts->count(), 'rows' => $parts],
            'orders' => $orders,
            'services' => $services,
        ];
    }

    private function link(string $permission, string $route, int $id): ?string
    {
        return Gate::allows($permission) ? route($route, $id) : null;
    }
}
