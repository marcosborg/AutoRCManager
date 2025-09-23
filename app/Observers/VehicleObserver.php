<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Models\Repair;
use App\Mail\VehicleStateChangedMail;
use Illuminate\Support\Facades\Mail;

class VehicleObserver
{
    public function updating(Vehicle $vehicle)
    {
        // Verifica se o general_state_id foi alterado
        if ($vehicle->isDirty('general_state_id')) {
            $newState = \App\Models\GeneralState::find($vehicle->general_state_id);

            // Se o novo estado for 4 (na oficina), criar Repair se ainda não existir
            if ($vehicle->general_state_id == 3 && $vehicle->client_id == 1) {
                $existingRepair = Repair::where('vehicle_id', $vehicle->id)->first();

                if (!$existingRepair) {
                    Repair::create([
                        'vehicle_id' => $vehicle->id,
                        // os outros campos serão preenchidos mais tarde
                    ]);
                }
            }

            // Enviar email se o estado tiver notification ativa
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
