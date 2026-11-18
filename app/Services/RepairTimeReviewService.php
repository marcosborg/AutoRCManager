<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RepairTimeReviewService
{
    public function build(Collection $logs, ?CarbonInterface $asOf = null): array
    {
        $asOf = $asOf ? CarbonImmutable::instance($asOf) : CarbonImmutable::now();
        $rows = $logs->map(function ($log) use ($asOf) {
            $attributes = $log->getAttributes();
            $open = ($attributes['finished_at'] ?? null) === null;
            $totals = $this->emptyTotals();
            $totals['open_count'] = $open ? 1 : 0;
            $startedAt = $this->date($attributes['started_at'] ?? null);
            $finishedAt = $open ? null : $this->date($attributes['finished_at']);
            $duration = $attributes['duration_minutes'] ?? null;

            if (! $startedAt || $startedAt->gt($asOf)
                || (! $open && (! $finishedAt || $finishedAt->lt($startedAt) || $finishedAt->gt($asOf)
                    || ! is_numeric($duration) || ! is_finite((float) $duration) || (float) $duration < 0))) {
                $totals['unconfirmed_count'] = 1;
            } elseif ($open) {
                $totals['open_minutes'] = (int) $startedAt->diffInMinutes($asOf);
            } else {
                $totals['closed_minutes'] = (int) $duration;
            }

            return [
                'user_id' => $log->user_id,
                'name' => $log->user?->name ?? 'Utilizador por identificar',
                'workshop_intervention_id' => $log->workshop_intervention_id,
                'title' => $log->workshop_intervention_id
                    ? ($log->workshopIntervention?->title ?? 'Tarefa por identificar')
                    : 'Trabalho geral da reparação',
                'totals' => $totals,
            ];
        });

        return [
            'as_of' => $asOf->format('Y-m-d H:i:s'),
            'totals' => $this->sum($rows),
            'mechanics' => $this->mechanics($rows),
            'interventions' => $rows->groupBy(fn ($row) => $row['workshop_intervention_id'] ?? 'general')
                ->map(fn ($group) => [
                    'workshop_intervention_id' => $group->first()['workshop_intervention_id'],
                    'title' => $group->first()['title'],
                    'mechanics' => $this->mechanics($group),
                    'totals' => $this->sum($group),
                ])->values()->all(),
        ];
    }

    private function mechanics(Collection $rows): array
    {
        return $rows->groupBy('user_id')->map(fn ($group) => [
            'user_id' => $group->first()['user_id'],
            'name' => $group->first()['name'],
            'totals' => $this->sum($group),
        ])->values()->all();
    }

    private function sum(Collection $rows): array
    {
        $totals = $this->emptyTotals();
        foreach (array_keys($totals) as $key) {
            $totals[$key] = (int) $rows->sum(fn ($row) => $row['totals'][$key]);
        }

        return $totals;
    }

    private function emptyTotals(): array
    {
        return ['closed_minutes' => 0, 'open_minutes' => 0, 'open_count' => 0, 'unconfirmed_count' => 0];
    }

    private function date($value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
