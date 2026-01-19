<?php

namespace App\Services;

use App\Models\OperationalUnit;
use App\Models\VehicleConsignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardKpiService
{
    public function build(?string $from, ?string $to, OperationalUnitReportService $reportService): array
    {
        $fromDate = $this->parseDate($from) ?? Carbon::today()->startOfMonth();
        $toDate = $this->parseDate($to) ?? Carbon::today();
        $fromDate = $fromDate->startOfDay();
        $toDate = $toDate->endOfDay();

        $report = $reportService->buildReport($fromDate->format(config('panel.date_format')), $toDate->format(config('panel.date_format')));
        $units = $report['units'];

        $totalCost = (float) $units->sum('total_cost');
        $totalRevenue = (float) $units->sum('total_revenue');
        $result = $totalRevenue - $totalCost;

        $consignments = VehicleConsignment::query()
            ->where('starts_at', '<=', $toDate)
            ->where(function ($query) use ($fromDate) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $fromDate);
            })
            ->get(['vehicle_id', 'to_unit_id', 'starts_at', 'ends_at']);

        $vehiclesActive = $consignments->pluck('vehicle_id')->unique()->count();
        $vehiclesMoved = $consignments
            ->whereBetween('starts_at', [$fromDate, $toDate])
            ->pluck('vehicle_id')
            ->unique()
            ->count();

        $unitCounts = $this->buildUnitVehicleCounts($consignments);
        $unitsWithCounts = $this->mergeUnitCounts($units, $unitCounts);

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'totals' => [
                'cost' => $totalCost,
                'revenue' => $totalRevenue,
                'result' => $result,
                'vehicles_active' => $vehiclesActive,
                'vehicles_moved' => $vehiclesMoved,
            ],
            'units' => $unitsWithCounts,
        ];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat(config('panel.date_format'), $value);
    }

    private function buildUnitVehicleCounts(Collection $consignments): Collection
    {
        return $consignments
            ->groupBy('to_unit_id')
            ->map(function (Collection $items) {
                return $items->pluck('vehicle_id')->unique()->count();
            });
    }

    private function mergeUnitCounts(Collection $units, Collection $unitCounts): Collection
    {
        $unitNames = OperationalUnit::pluck('name', 'id');

        return $units->map(function ($unit) use ($unitCounts, $unitNames) {
            $unitId = $unit['unit_id'];
            $unit['unit_name'] = $unit['unit_name'] ?? $unitNames->get($unitId, 'N/A');
            $unit['vehicle_count'] = (int) ($unitCounts->get($unitId) ?? 0);

            return $unit;
        });
    }
}
