<?php

namespace App\Observers;

use App\Domain\Repairs\RepairStatus;
use App\Models\Repair;

class RepairObserver
{
    public function updating(Repair $repair)
    {
        // Verifica se o estado da reparação foi alterado
        if ($repair->isDirty('repair_state_id') && $repair->repair_state_id == RepairStatus::CLOSED_ID) {
            // Se foi para o estado 3, atualizar o estado geral da viatura para 2
            $vehicle = $repair->vehicle;

            if ($vehicle) {
                $vehicle->general_state_id = 2;
                $vehicle->save();
            }
        }
    }
}
