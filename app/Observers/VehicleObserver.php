<?php

namespace App\Observers;

use App\Mail\VehicleStateChangedMail;
use App\Models\Vehicle;
use App\Models\VehicleStateTransfer;
use App\Services\ManagementAlertService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class VehicleObserver
{
    public function updating(Vehicle $vehicle)
    {
        if ($vehicle->isDirty('general_state_id')) {
            $originalStateId = $vehicle->getOriginal('general_state_id');
            $originalSnapshot = $vehicle->getOriginal();

            VehicleStateTransfer::create([
                'vehicle_id' => $vehicle->id,
                'from_general_state_id' => $originalStateId,
                'to_general_state_id' => $vehicle->general_state_id,
                'user_id' => Auth::id(),
                'fuel_level' => $originalSnapshot['fuel'] ?? null,
                'snapshot' => $originalSnapshot,
            ]);

            $newState = \App\Models\GeneralState::find($vehicle->general_state_id);

            if ($newState && $newState->notification) {
                $emails = array_map('trim', explode(',', $newState->emails));

                foreach ($emails as $email) {
                    Mail::to($email)->queue(new VehicleStateChangedMail(
                        $vehicle->license,
                        $newState->message
                    ));
                }
            }

            if ($newState && $this->isStockAvailableState((string) $newState->name)) {
                app(ManagementAlertService::class)->stockAvailable($vehicle);
            }
        }

        if ($vehicle->isDirty('sale_date') && ! $vehicle->getOriginal('sale_date') && $vehicle->sale_date) {
            app(ManagementAlertService::class)->vehicleSold($vehicle);
        }
    }

    private function isStockAvailableState(string $name): bool
    {
        $normalized = str($name)
            ->lower()
            ->ascii()
            ->squish()
            ->toString();

        return in_array($normalized, ['em stock disponivel', 'stock disponivel'], true);
    }
}
