<?php

namespace App\Http\Controllers\Admin;

use App\Models\CalendarTask;
use App\Models\Client;
use App\Models\GeneralState;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\PartPayment;
use App\Models\Vehicle;
use App\Models\VehicleStateTransfer;
use App\Support\RolePreview;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $today->copy()->endOfMonth()->format('Y-m-d');
        $startOfYear = $today->copy()->startOfYear()->format('Y-m-d');
        $endOfYear = $today->copy()->endOfYear()->format('Y-m-d');

        $tasksToday = Schema::hasTable('calendar_tasks')
            ? CalendarTask::query()
                ->visibleTo(auth()->user())
                ->whereNull('completed_at')
                ->whereDate('due_date', '<=', $today->format('Y-m-d'))
                ->orderBy('due_date')
                ->get()
            : collect();

        $stateChanges = VehicleStateTransfer::with(['vehicle.brand', 'from_general_state', 'to_general_state', 'user'])
            ->when(Schema::hasColumn('vehicle_state_transfers', 'checked_at'), fn ($query) => $query->whereNull('checked_at'))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $soldThisMonth = Vehicle::with(['brand', 'client'])
            ->whereNotNull('sale_date')
            ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
            ->orderByDesc('sale_date')
            ->get();

        $soldThisYear = Vehicle::query()
            ->whereNotNull('sale_date')
            ->whereBetween('sale_date', [$startOfYear, $endOfYear])
            ->get();

        $latestSoldVehicles = Vehicle::with(['brand', 'client'])
            ->whereNotNull('sale_date')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $adjudicationStateId = GeneralState::query()
            ->whereRaw('LOWER(name) = ?', ['adjudicação'])
            ->orWhereRaw('LOWER(name) = ?', ['adjudicacao'])
            ->orderBy('id')
            ->value('id');

        $latestAdjudications = $adjudicationStateId
            ? VehicleStateTransfer::with(['vehicle.brand', 'vehicle.client', 'from_general_state', 'to_general_state'])
                ->where('to_general_state_id', $adjudicationStateId)
                ->whereHas('vehicle', fn ($query) => $query->where('general_state_id', $adjudicationStateId))
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('vehicle_state_transfers as later_transfers')
                        ->whereColumn('later_transfers.vehicle_id', 'vehicle_state_transfers.vehicle_id')
                        ->whereColumn('later_transfers.id', '>', 'vehicle_state_transfers.id');
                })
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
            : collect();

        if ($latestAdjudications->isEmpty() && $adjudicationStateId) {
            $latestAdjudications = Vehicle::with(['brand', 'client'])
                ->where('general_state_id', $adjudicationStateId)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->limit(8)
                ->get()
                ->map(fn (Vehicle $vehicle) => (object) [
                    'vehicle' => $vehicle,
                    'created_at' => $vehicle->updated_at,
                ]);
        }

        $business = [
            'month_count' => $soldThisMonth->count(),
            'month_total' => $soldThisMonth->sum(fn (Vehicle $vehicle) => $this->salesTotal($vehicle)),
            'year_count' => $soldThisYear->count(),
            'year_total' => $soldThisYear->sum(fn (Vehicle $vehicle) => $this->salesTotal($vehicle)),
            'stock_count' => Vehicle::query()->whereNull('sale_date')->count(),
            'clients_count' => Client::query()->count(),
        ];

        $partOrderStats = Schema::hasTable('part_orders')
            ? [
                'delayed_orders' => PartOrder::query()
                    ->where(function ($query) use ($today) {
                        $query->where('status', 'delayed')
                            ->orWhere(function ($subQuery) use ($today) {
                                $subQuery->whereDate('expected_delivery_date', '<', $today->format('Y-m-d'))
                                    ->whereNull('actual_delivery_date')
                                    ->whereNotIn('status', ['received', 'cancelled']);
                            });
                    })
                    ->count(),
                'pending_items' => PartOrderItem::query()
                    ->whereNotIn('status', ['received', 'installed', 'returned'])
                    ->count(),
                'overdue_payments' => PartPayment::query()
                    ->whereDate('due_date', '<', $today->format('Y-m-d'))
                    ->whereNotIn('payment_status', ['paid', 'cancelled'])
                    ->count(),
                'vehicles_waiting_parts' => PartOrder::query()
                    ->whereNotNull('vehicle_id')
                    ->whereIn('status', ['requesting_quotes', 'ordered', 'partially_received', 'delayed'])
                    ->distinct('vehicle_id')
                    ->count('vehicle_id'),
            ]
            : [
                'delayed_orders' => 0,
                'pending_items' => 0,
                'overdue_payments' => 0,
                'vehicles_waiting_parts' => 0,
            ];

        $showIucAlerts = RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm']);
        $currentIucMonthLabel = $this->iucMonthLabel($today);
        $iucDueVehicles = $showIucAlerts ? $this->iucDueVehicles($today, 50) : collect();
        $recentDavVehicles = Schema::hasColumn('vehicles', 'dav_created_at')
            ? Vehicle::with(['brand', 'general_state'])
                ->whereNotNull('dav_created_at')
                ->whereBetween('dav_created_at', [now()->subDays(7), now()])
                ->orderByDesc('dav_created_at')
                ->get()
            : collect();

        return view('home', compact(
            'tasksToday',
            'stateChanges',
            'business',
            'partOrderStats',
            'latestSoldVehicles',
            'latestAdjudications',
            'iucDueVehicles',
            'currentIucMonthLabel',
            'showIucAlerts',
            'recentDavVehicles'
        ));
    }

    public function exportIucDue()
    {
        abort_if(
            ! RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm']),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $today = Carbon::today();
        $monthLabel = $this->iucMonthLabel($today);
        $vehicles = $this->iucDueVehicles($today);
        $filename = 'iuc-a-pagamento-'.$today->format('Y-m').'.xls';

        return response()->streamDownload(function () use ($vehicles, $monthLabel) {
            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<table border="1">';
            echo '<thead><tr>';
            foreach (['Matricula', 'Marca', 'Modelo', 'Estado', 'Mes IUC', 'Valor IUC'] as $heading) {
                echo '<th>'.htmlspecialchars($heading, ENT_QUOTES, 'UTF-8').'</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($vehicles as $vehicle) {
                $license = $vehicle->license ?: $vehicle->foreign_license ?: 'Sem matricula';
                $values = [
                    $license,
                    $vehicle->brand->name ?? '',
                    $vehicle->model ?? '',
                    $vehicle->general_state->name ?? '',
                    $vehicle->mes_iuc ?? $monthLabel,
                    $vehicle->iuc_price !== null ? number_format((float) $vehicle->iuc_price, 2, ',', '.') : '',
                ];

                echo '<tr>';
                foreach ($values as $value) {
                    echo '<td style="mso-number-format:\'@\';">'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'</td>';
                }
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function salesTotal(Vehicle $vehicle): float
    {
        return (float) ($vehicle->pvp ?? 0)
            + (float) ($vehicle->sales_iuc ?? 0)
            + (float) ($vehicle->sales_tow ?? 0)
            + (float) ($vehicle->sales_transfer ?? 0)
            + (float) ($vehicle->sales_others ?? 0);
    }

    private function iucDueVehicles(Carbon $today, ?int $limit = null)
    {
        if (! Schema::hasColumn('vehicles', 'mes_iuc')) {
            return collect();
        }

        $query = Vehicle::with(['brand', 'general_state'])
            ->where(function ($query) use ($today) {
                foreach ($this->iucMonthSearchValues($today) as $monthValue) {
                    $query->orWhereRaw('UPPER(TRIM(mes_iuc)) = ?', [$monthValue]);
                }
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('iuc_paid_date')
                    ->orWhereYear('iuc_paid_date', '<', $today->year);
            })
            ->orderByRaw('COALESCE(NULLIF(license, ""), foreign_license, id)');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function iucMonthLabel(Carbon $date): string
    {
        return $this->ptMonths()[(int) $date->format('n')];
    }

    private function iucMonthSearchValues(Carbon $date): array
    {
        $month = (int) $date->format('n');
        $values = [
            (string) $month,
            $date->format('m'),
            $this->ptMonths()[$month],
            $this->ptMonthsWithAccents()[$month],
        ];

        return array_values(array_unique(array_map(
            fn ($value) => mb_strtoupper(trim((string) $value), 'UTF-8'),
            $values
        )));
    }

    private function ptMonths(): array
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Marco',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
    }

    private function ptMonthsWithAccents(): array
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
    }
}
