<?php

namespace App\Services;

use App\Domain\Finance\AccountDepartments;
use App\Models\AccountOperation;
use App\Models\OperationalUnit;
use App\Models\VehicleConsignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OperationalUnitReportService
{
    public function buildReport(?string $from, ?string $to): array
    {
        $fromDate = $this->parseDate($from) ?? Carbon::today()->startOfMonth();
        $toDate = $this->parseDate($to) ?? Carbon::today();
        $fromDate = $fromDate->startOfDay();
        $toDate = $toDate->endOfDay();

        $consignments = VehicleConsignment::with(['to_unit', 'vehicle'])
            ->where('starts_at', '<=', $toDate)
            ->where(function ($query) use ($fromDate) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $fromDate);
            })
            ->orderBy('starts_at')
            ->get();

        $vehicleConsignments = $consignments
            ->groupBy('vehicle_id')
            ->map(function (Collection $items) {
                return $items->sortBy('starts_at')->values();
            });

        $vehicleIds = $vehicleConsignments->keys()->all();

        $report = [];
        $units = OperationalUnit::orderBy('name')->get()->keyBy('id');

        foreach ($units as $unit) {
            $report[$unit->id] = [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'total_cost' => 0.0,
                'total_revenue' => 0.0,
                'result' => 0.0,
            ];
        }

        if (empty($vehicleIds)) {
            return [
                'from' => $fromDate,
                'to' => $toDate,
                'units' => collect($report)->values(),
            ];
        }

        $operations = AccountOperation::with(['account_item.account_category'])
            ->whereIn('vehicle_id', $vehicleIds)
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
                    ->orWhere(function ($sub) use ($fromDate, $toDate) {
                        $sub->whereNull('date')
                            ->whereBetween('created_at', [$fromDate, $toDate]);
                    });
            })
            ->orderBy('date')
            ->get();

        foreach ($operations as $operation) {
            $eventDate = $operation->date
                ? Carbon::parse($operation->date)->startOfDay()
                : Carbon::parse($operation->created_at);

            $consignment = $this->findConsignmentForDate(
                $vehicleConsignments->get($operation->vehicle_id, collect()),
                $eventDate
            );

            if (! $consignment) {
                continue;
            }

            $unitId = $consignment->to_unit_id;
            if (! isset($report[$unitId])) {
                continue;
            }

            $departmentId = optional($operation->account_item->account_category)->account_department_id;
            $amount = (float) ($operation->total ?? 0);

            if ($amount < 0) {
                $report[$unitId]['total_cost'] += abs($amount);
            } elseif ($departmentId === AccountDepartments::REVENUE) {
                $report[$unitId]['total_revenue'] += $amount;
            } else {
                $report[$unitId]['total_cost'] += $amount;
            }
        }

        foreach ($report as &$unitRow) {
            $unitRow['result'] = $unitRow['total_revenue'] - $unitRow['total_cost'];
        }

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'units' => collect($report)->values(),
        ];
    }

    private function findConsignmentForDate(Collection $consignments, Carbon $date): ?VehicleConsignment
    {
        foreach ($consignments as $consignment) {
            $start = Carbon::parse($consignment->starts_at);
            $end = $consignment->ends_at ? Carbon::parse($consignment->ends_at) : null;

            if ($date->lt($start)) {
                continue;
            }

            if ($end && $date->gt($end)) {
                continue;
            }

            return $consignment;
        }

        return null;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat(config('panel.date_format'), $value);
    }
}
