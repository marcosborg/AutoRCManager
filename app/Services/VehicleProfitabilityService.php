<?php

namespace App\Services;

use App\Models\AccountOperation;
use App\Models\Department;
use App\Models\Vehicle;

class VehicleProfitabilityService
{
    public function build(Vehicle $vehicle): array
    {
        $vehicle->loadMissing([
            'brand',
            'client',
            'purchase_price_histories',
            'repairs.parts',
            'repairs.part_orders.items',
            'part_orders.items',
            'external_services',
            'generic_payments',
        ]);

        $originPurchase = $this->originPurchase($vehicle);
        $workshopTransfer = $this->workshopTransfer($vehicle);
        $workshopParts = $this->repairPartsTotal($vehicle) + $this->partOrdersTotal($vehicle);
        $workshopExternalServices = $this->externalServicesTotal($vehicle);
        $workshopOperations = $this->departmentOperationsTotal($vehicle, 'oficina');
        $workshopCosts = $workshopParts + $workshopExternalServices + $workshopOperations;
        $standEntry = $workshopTransfer > 0 ? $workshopTransfer + $workshopCosts : (float) ($vehicle->purchase_price ?? 0);
        $standGenericCosts = (float) $vehicle->generic_payments->sum('amount');
        $standOperations = $this->departmentOperationsTotal($vehicle, 'stand');
        $standCosts = $standGenericCosts + $standOperations;
        $finalSale = $this->finalSale($vehicle);
        $globalCost = $originPurchase + $workshopCosts + $standCosts;

        return [
            'vehicle_label' => $this->vehicleLabel($vehicle),
            'origin_purchase' => round($originPurchase, 2),
            'workshop_transfer' => round($workshopTransfer, 2),
            'workshop_parts' => round($workshopParts, 2),
            'workshop_external_services' => round($workshopExternalServices, 2),
            'workshop_operations' => round($workshopOperations, 2),
            'workshop_costs' => round($workshopCosts, 2),
            'stand_entry' => round($standEntry, 2),
            'stand_generic_costs' => round($standGenericCosts, 2),
            'stand_operations' => round($standOperations, 2),
            'stand_costs' => round($standCosts, 2),
            'final_sale' => round($finalSale, 2),
            'global_cost' => round($globalCost, 2),
            'global_margin' => round($finalSale - $globalCost, 2),
            'salvage_margin' => round($workshopTransfer > 0 ? $workshopTransfer - $originPurchase : 0, 2),
            'workshop_margin' => round($workshopTransfer > 0 ? $standEntry - $workshopTransfer : 0, 2),
            'stand_margin' => round($finalSale > 0 ? $finalSale - $standEntry - $standCosts : 0, 2),
            'has_workshop_transfer' => $workshopTransfer > 0,
            'has_final_sale' => $finalSale > 0,
        ];
    }

    private function originPurchase(Vehicle $vehicle): float
    {
        $firstWorkshopSale = $vehicle->purchase_price_histories
            ->where('reason', 'workshop_sale')
            ->sortBy('id')
            ->first();

        if ($firstWorkshopSale) {
            return (float) ($firstWorkshopSale->previous_purchase_price ?? 0);
        }

        return (float) ($vehicle->purchase_price ?? 0);
    }

    private function workshopTransfer(Vehicle $vehicle): float
    {
        $latestWorkshopSale = $vehicle->purchase_price_histories
            ->where('reason', 'workshop_sale')
            ->sortByDesc('id')
            ->first();

        if ($latestWorkshopSale) {
            return (float) $latestWorkshopSale->sale_price;
        }

        if ($this->isWorkshopClient($vehicle)) {
            return $this->salesFinalTotal($vehicle);
        }

        return 0.0;
    }

    private function finalSale(Vehicle $vehicle): float
    {
        if (! $vehicle->getRawOriginal('sale_date') || $this->isWorkshopClient($vehicle)) {
            return 0.0;
        }

        return $this->salesFinalTotal($vehicle);
    }

    private function salesFinalTotal(Vehicle $vehicle): float
    {
        return (float) ($vehicle->pvp ?? 0)
            + (float) ($vehicle->sales_iuc ?? 0)
            + (float) ($vehicle->sales_tow ?? 0)
            + (float) ($vehicle->sales_transfer ?? 0)
            + (float) ($vehicle->sales_others ?? 0);
    }

    private function repairPartsTotal(Vehicle $vehicle): float
    {
        return (float) $vehicle->repairs
            ->flatMap(fn ($repair) => $repair->parts)
            ->sum('amount');
    }

    private function partOrdersTotal(Vehicle $vehicle): float
    {
        $orders = $vehicle->part_orders
            ->concat($vehicle->repairs->flatMap(fn ($repair) => $repair->part_orders))
            ->unique('id');

        return (float) $orders
            ->flatMap(fn ($order) => $order->items)
            ->sum(fn ($item) => (float) ($item->total_final ?? $item->total_estimated ?? 0));
    }

    private function departmentOperationsTotal(Vehicle $vehicle, string $departmentName): float
    {
        $departmentIds = Department::query()
            ->whereRaw('LOWER(name) = ?', [$departmentName])
            ->pluck('id');

        if ($departmentIds->isEmpty()) {
            return 0.0;
        }

        return (float) AccountOperation::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('department_id', $departmentIds)
            ->where('movement_type', AccountOperation::TYPE_OUTCOME)
            ->sum('total');
    }

    private function externalServicesTotal(Vehicle $vehicle): float
    {
        return (float) $vehicle->external_services
            ->where('status', '!=', 'cancelled')
            ->sum('amount');
    }

    private function isWorkshopClient(Vehicle $vehicle): bool
    {
        return mb_strtolower((string) ($vehicle->client->name ?? '')) === 'oficina';
    }

    private function vehicleLabel(Vehicle $vehicle): string
    {
        return $vehicle->license
            ?: $vehicle->foreign_license
            ?: ('#' . $vehicle->id);
    }
}
