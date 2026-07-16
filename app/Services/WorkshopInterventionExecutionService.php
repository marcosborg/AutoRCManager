<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkshopIntervention;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkshopInterventionExecutionService
{
    public function __construct(private readonly RepairWorkLogService $workLogs)
    {
    }

    public function start(WorkshopIntervention $intervention, User $user): void
    {
        DB::transaction(function () use ($intervention, $user) {
            $intervention = WorkshopIntervention::query()->lockForUpdate()->findOrFail($intervention->id);
            $this->assertAssigned($intervention, $user);

            if (in_array($intervention->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['intervention' => 'Este trabalho já não pode ser iniciado.']);
            }

            $this->workLogs->start($intervention->repair, $user, $intervention);

            if ($intervention->status === 'planned') {
                $intervention->update(['status' => 'in_progress']);
            }
        });
    }

    public function finish(WorkshopIntervention $intervention, User $user): void
    {
        $this->assertAssigned($intervention, $user);

        $this->workLogs->finish($intervention->repair, $user, $intervention);
    }

    public function complete(WorkshopIntervention $intervention, User $user): void
    {
        $this->assertAssigned($intervention, $user);

        DB::transaction(function () use ($intervention, $user) {
            $intervention = WorkshopIntervention::query()->lockForUpdate()->findOrFail($intervention->id);
            if ($intervention->status === 'cancelled') {
                throw ValidationException::withMessages(['intervention' => 'Um trabalho cancelado não pode ser concluído.']);
            }

            $this->workLogs->closeForIntervention($intervention);

            $intervention->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by_id' => $user->id,
            ]);
        });
    }

    public function cancel(WorkshopIntervention $intervention): void
    {
        DB::transaction(function () use ($intervention) {
            $intervention = WorkshopIntervention::query()->lockForUpdate()->findOrFail($intervention->id);
            $this->workLogs->closeForIntervention($intervention);

            $intervention->update(['status' => 'cancelled']);
        });
    }

    private function assertAssigned(WorkshopIntervention $intervention, User $user): void
    {
        if (! $intervention->mechanics()->whereKey($user->id)->exists()) {
            throw ValidationException::withMessages(['intervention' => 'Este trabalho não lhe está atribuído.']);
        }
    }

}
