<?php

namespace App\Services;

use App\Models\GeneralState;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleSuspendedSale;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VehicleSuspendedSaleService
{
    public const STATE_NAME = 'Venda Suspensa';

    public function suspend(Vehicle $vehicle, int $clientId, ?User $user = null, ?string $notes = null): VehicleSuspendedSale
    {
        return DB::transaction(function () use ($vehicle, $clientId, $user, $notes) {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicle->id);

            if ($vehicle->getRawOriginal('sale_date')) {
                throw new RuntimeException('Nao e possivel suspender uma viatura com venda finalizada.');
            }

            if ($this->activeSuspensionFor($vehicle)->exists()) {
                throw new RuntimeException('Esta viatura ja tem uma venda suspensa ativa.');
            }

            $suspendedStateId = $this->suspendedStateId();
            $previousStateId = $vehicle->general_state_id;

            $suspendedSale = VehicleSuspendedSale::create([
                'vehicle_id' => $vehicle->id,
                'client_id' => $clientId,
                'previous_general_state_id' => $previousStateId,
                'status' => VehicleSuspendedSale::STATUS_ACTIVE,
                'suspended_at' => now(),
                'suspended_by_id' => $user?->id,
                'notes' => $notes,
            ]);

            $vehicle->update([
                'client_id' => $clientId,
                'general_state_id' => $suspendedStateId,
            ]);

            return $suspendedSale->fresh(['client', 'previous_general_state', 'suspended_by']);
        });
    }

    public function cancel(Vehicle $vehicle, ?User $user = null): ?VehicleSuspendedSale
    {
        return DB::transaction(function () use ($vehicle, $user) {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicle->id);
            $suspendedSale = $this->activeSuspensionFor($vehicle)->lockForUpdate()->first();

            if (! $suspendedSale) {
                return null;
            }

            $suspendedSale->update([
                'status' => VehicleSuspendedSale::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by_id' => $user?->id,
            ]);

            $updates = [];
            if ((int) $vehicle->general_state_id === (int) $this->suspendedStateId()) {
                $updates['general_state_id'] = $suspendedSale->previous_general_state_id;
            }

            if (! $vehicle->getRawOriginal('sale_date') && (int) $vehicle->client_id === (int) $suspendedSale->client_id) {
                $updates['client_id'] = null;
            }

            if ($updates !== []) {
                $vehicle->update($updates);
            }

            return $suspendedSale->fresh(['client', 'previous_general_state', 'cancelled_by']);
        });
    }

    public function convertActiveForVehicle(Vehicle $vehicle, ?User $user = null): ?VehicleSuspendedSale
    {
        if (! $vehicle->getRawOriginal('sale_date')) {
            return null;
        }

        return DB::transaction(function () use ($vehicle, $user) {
            $suspendedSale = VehicleSuspendedSale::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('status', VehicleSuspendedSale::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $suspendedSale) {
                return null;
            }

            $suspendedSale->update([
                'status' => VehicleSuspendedSale::STATUS_CONVERTED,
                'converted_at' => now(),
                'converted_by_id' => $user?->id,
            ]);

            return $suspendedSale->fresh(['client', 'previous_general_state', 'converted_by']);
        });
    }

    public function suspendedStateId(): int
    {
        $state = GeneralState::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(self::STATE_NAME)])
            ->first();

        if (! $state) {
            $state = GeneralState::create(['name' => self::STATE_NAME]);
        }

        return (int) $state->id;
    }

    private function activeSuspensionFor(Vehicle $vehicle)
    {
        return VehicleSuspendedSale::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', VehicleSuspendedSale::STATUS_ACTIVE)
            ->latest('id');
    }
}
