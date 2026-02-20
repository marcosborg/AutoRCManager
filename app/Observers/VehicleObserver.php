<?php

namespace App\Observers;

use App\Domain\Repairs\RepairRules;
use App\Mail\VehicleStateChangedMail;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\VehicleStateTransfer;
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

            if ($vehicle->general_state_id == 3 && $vehicle->client_id == 1) {
                if (! RepairRules::hasOpenRepairs($vehicle->id)) {
                    Repair::create([
                        'vehicle_id' => $vehicle->id,
                    ]);
                }
            }

            if ($newState && $newState->notification) {
                $emails = array_map('trim', explode(',', $newState->emails));

                foreach ($emails as $email) {
                    Mail::to($email)->queue(new VehicleStateChangedMail(
                        $vehicle->license,
                        $newState->message
                    ));
                }
            }
        }
    }
}
