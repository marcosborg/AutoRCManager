<?php

namespace App\Services;

use App\Models\Repair;
use App\Models\RepairWorkLog;
use App\Models\User;
use App\Models\WorkshopIntervention;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairWorkLogService
{
    public function start(Repair $repair, User $user, ?WorkshopIntervention $intervention = null): void
    {
        DB::transaction(function () use ($repair, $user, $intervention) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $repair = Repair::query()->whereKey($repair->id)->lockForUpdate()->firstOrFail();

            if ($repair->getRawOriginal('repair_finished_at')) {
                throw ValidationException::withMessages([
                    $intervention ? 'intervention' : 'repair_work' => 'A reparação já está finalizada.',
                ]);
            }

            $openLog = RepairWorkLog::query()
                ->where('user_id', $user->id)
                ->whereNull('finished_at')
                ->lockForUpdate()
                ->first();

            if ($openLog) {
                $sameRepair = (int) $openLog->repair_id === (int) $repair->id;
                $sameIntervention = (int) ($openLog->workshop_intervention_id ?? 0) === (int) ($intervention?->id ?? 0);

                if ($sameRepair && $sameIntervention) {
                    return;
                }

                throw ValidationException::withMessages([
                    $intervention ? 'intervention' : 'repair_work' => 'Já tem outro trabalho em curso. Termine-o antes de iniciar este.',
                ]);
            }

            RepairWorkLog::create([
                'repair_id' => $repair->id,
                'workshop_intervention_id' => $intervention?->id,
                'user_id' => $user->id,
                'started_at' => now(),
            ]);
        });
    }

    public function finish(Repair $repair, User $user, ?WorkshopIntervention $intervention = null): void
    {
        DB::transaction(function () use ($repair, $user, $intervention) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $log = RepairWorkLog::query()
                ->where('repair_id', $repair->id)
                ->where('user_id', $user->id)
                ->where('workshop_intervention_id', $intervention?->id)
                ->whereNull('finished_at')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $log) {
                throw ValidationException::withMessages([
                    $intervention ? 'intervention' : 'repair_work' => 'Não existe trabalho em curso para este mecânico.',
                ]);
            }

            $this->close($log);
        });
    }

    public function closeForRepair(Repair $repair): void
    {
        RepairWorkLog::query()
            ->where('repair_id', $repair->id)
            ->whereNull('finished_at')
            ->lockForUpdate()
            ->get()
            ->each(fn (RepairWorkLog $log) => $this->close($log));
    }

    public function closeForIntervention(WorkshopIntervention $intervention): void
    {
        RepairWorkLog::query()
            ->where('workshop_intervention_id', $intervention->id)
            ->whereNull('finished_at')
            ->lockForUpdate()
            ->get()
            ->each(fn (RepairWorkLog $log) => $this->close($log));
    }

    private function close(RepairWorkLog $log): void
    {
        $finishedAt = now();
        $log->update([
            'finished_at' => $finishedAt,
            'duration_minutes' => Carbon::parse($log->started_at)->diffInMinutes($finishedAt),
        ]);
    }
}
