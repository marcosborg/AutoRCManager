<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Mail\VehicleStateChangedMail;
use Illuminate\Support\Facades\Mail;

class VehicleObserver
{
    public function updating(Vehicle $vehicle)
    {
        // Verifica se o campo general_state_id está a ser alterado
        if ($vehicle->isDirty('general_state_id')) {
            $newState = $vehicle->general_state;

            // Só se o novo estado tiver notification ativada
            if ($newState && $newState->notification) {
                $emails = array_map('trim', explode(',', $newState->emails));

                if (!empty($emails)) {
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
}
