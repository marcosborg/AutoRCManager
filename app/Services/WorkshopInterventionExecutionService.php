<?php

namespace App\Services;

use App\Models\RepairWorkLog;
use App\Models\User;
use App\Models\WorkshopIntervention;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkshopInterventionExecutionService
{
    public function start(WorkshopIntervention $intervention, User $user): void
    {
        $this->assertAssigned($intervention, $user);

        if (in_array($intervention->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['intervention' => 'Este trabalho já não pode ser iniciado.']);
        }

        DB::transaction(function () use ($intervention, $user) {
            $openLog = RepairWorkLog::query()
                ->where('user_id', $user->id)
                ->whereNull('finished_at')
                ->lockForUpdate()
                ->first();

            if ($openLog) {
                if ((int) $openLog->workshop_intervention_id === (int) $intervention->id) {
                    return;
                }
                throw ValidationException::withMessages(['intervention' => 'Já tem outro trabalho em curso. Termine-o antes de iniciar este.']);
            }

            RepairWorkLog::create([
                'repair_id' => $intervention->repair_id,
                'workshop_intervention_id' => $intervention->id,
                'user_id' => $user->id,
                'started_at' => now(),
            ]);

            if ($intervention->status === 'planned') {
                $intervention->update(['status' => 'in_progress']);
            }
        });
    }

    public function finish(WorkshopIntervention $intervention, User $user): void
    {
        $this->assertAssigned($intervention, $user);

        DB::transaction(function () use ($intervention, $user) {
            $log = RepairWorkLog::query()
                ->where('workshop_intervention_id', $intervention->id)
                ->where('user_id', $user->id)
                ->whereNull('finished_at')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $log) {
                throw ValidationException::withMessages(['intervention' => 'Não existe trabalho em curso para este mecânico.']);
            }

            $this->closeLog($log);
        });
    }

    public function complete(WorkshopIntervention $intervention, User $user): void
    {
        $this->assertAssigned($intervention, $user);

        DB::transaction(function () use ($intervention, $user) {
            $intervention = WorkshopIntervention::query()->lockForUpdate()->findOrFail($intervention->id);
            if ($intervention->status === 'cancelled') {
                throw ValidationException::withMessages(['intervention' => 'Um trabalho cancelado não pode ser concluído.']);
            }

            RepairWorkLog::query()
                ->where('workshop_intervention_id', $intervention->id)
                ->whereNull('finished_at')
                ->lockForUpdate()
                ->get()
                ->each(fn (RepairWorkLog $log) => $this->closeLog($log));

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
            RepairWorkLog::query()
                ->where('workshop_intervention_id', $intervention->id)
                ->whereNull('finished_at')
                ->lockForUpdate()
                ->get()
                ->each(fn (RepairWorkLog $log) => $this->closeLog($log));

            $intervention->update(['status' => 'cancelled']);
        });
    }

    private function assertAssigned(WorkshopIntervention $intervention, User $user): void
    {
        if (! $intervention->mechanics()->whereKey($user->id)->exists()) {
            throw ValidationException::withMessages(['intervention' => 'Este trabalho não lhe está atribuído.']);
        }
    }

    private function closeLog(RepairWorkLog $log): void
    {
        $finishedAt = now();
        $log->update([
            'finished_at' => $finishedAt,
            'duration_minutes' => Carbon::parse($log->started_at)->diffInMinutes($finishedAt),
        ]);
    }
}
