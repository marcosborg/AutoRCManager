<?php

namespace Tests\Unit;

use App\Models\RepairWorkLog;
use App\Models\User;
use App\Models\WorkshopIntervention;
use App\Services\RepairTimeReviewService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class RepairTimeReviewServiceTest extends TestCase
{
    private function log(array $attributes = [], ?string $name = 'Mecânico A', ?string $task = null): RepairWorkLog
    {
        $log = new RepairWorkLog();
        $log->setDateFormat('Y-m-d H:i:s');
        $log->setRawAttributes(array_merge([
            'id' => 1,
            'repair_id' => 1,
            'user_id' => 10,
            'workshop_intervention_id' => null,
            'started_at' => '2026-11-18 09:00:00',
            'finished_at' => '2026-11-18 10:00:00',
            'duration_minutes' => 60,
        ], $attributes), true);
        $log->setRelation('user', $name === null ? null : new User(['name' => $name]));
        $log->setRelation('workshopIntervention', $task === null ? null : new WorkshopIntervention(['title' => $task]));

        return $log;
    }

    private function review(array $logs, string $at = '2026-11-18 12:00:00'): array
    {
        return (new RepairTimeReviewService())->build(collect($logs), CarbonImmutable::parse($at));
    }

    public function test_empty_history_has_no_work_or_unknown_values(): void
    {
        $review = $this->review([]);

        $this->assertSame(0, $review['totals']['closed_minutes']);
        $this->assertSame(0, $review['totals']['open_minutes']);
        $this->assertSame(0, $review['totals']['open_count']);
        $this->assertSame(0, $review['totals']['unconfirmed_count']);
        $this->assertSame([], $review['mechanics']);
        $this->assertSame([], $review['interventions']);
    }

    public function test_simultaneous_mechanics_count_as_person_time_and_are_grouped_by_identity(): void
    {
        $review = $this->review([
            $this->log(),
            $this->log(['id' => 2, 'user_id' => 11]), // Same display name, different person.
            $this->log(['id' => 3, 'started_at' => '2026-11-18 10:00:00', 'finished_at' => '2026-11-18 10:30:00', 'duration_minutes' => 30]),
        ]);

        $this->assertSame(150, $review['totals']['closed_minutes']);
        $this->assertCount(2, $review['mechanics']);
        $mechanics = collect($review['mechanics'])->keyBy('user_id');
        $this->assertSame(90, $mechanics[10]['totals']['closed_minutes']);
        $this->assertSame(60, $mechanics[11]['totals']['closed_minutes']);
        $this->assertSame(150, $review['interventions'][0]['totals']['closed_minutes']);
    }

    public function test_running_work_is_provisional_and_ignores_any_stale_saved_duration(): void
    {
        $review = $this->review([
            $this->log(),
            $this->log(['id' => 2, 'started_at' => '2026-11-18 11:49:30', 'finished_at' => null, 'duration_minutes' => 999]),
        ]);

        $this->assertSame(60, $review['totals']['closed_minutes']);
        $this->assertSame(10, $review['totals']['open_minutes']);
        $this->assertSame(1, $review['totals']['open_count']);
        $this->assertSame(0, $review['totals']['unconfirmed_count']);
    }

    public function test_unconfirmed_closed_entries_are_not_silently_recalculated_but_zero_is_preserved(): void
    {
        $review = $this->review([
            $this->log(['duration_minutes' => null]),
            $this->log(['id' => 2, 'duration_minutes' => -1]),
            $this->log(['id' => 3, 'started_at' => null]),
            $this->log(['id' => 4, 'finished_at' => '2026-11-18 08:59:00']),
            $this->log(['id' => 5, 'started_at' => 'not-a-date']),
            $this->log(['id' => 6, 'duration_minutes' => 0]),
        ]);

        $this->assertSame(0, $review['totals']['closed_minutes']);
        $this->assertSame(5, $review['totals']['unconfirmed_count']);
        $this->assertSame(0, $review['totals']['open_count']);
    }

    public function test_future_dates_and_missing_open_start_are_flagged_without_negative_provisional_time(): void
    {
        $review = $this->review([
            $this->log(['finished_at' => '2026-11-18 13:00:00']),
            $this->log(['id' => 2, 'started_at' => '2026-11-18 13:00:00', 'finished_at' => null, 'duration_minutes' => null]),
            $this->log(['id' => 3, 'started_at' => null, 'finished_at' => null, 'duration_minutes' => null]),
        ]);

        $this->assertSame(0, $review['totals']['closed_minutes']);
        $this->assertSame(0, $review['totals']['open_minutes']);
        $this->assertSame(2, $review['totals']['open_count']);
        $this->assertSame(3, $review['totals']['unconfirmed_count']);
    }

    public function test_task_breakdown_reconciles_to_totals_including_general_work(): void
    {
        $review = $this->review([
            $this->log(['workshop_intervention_id' => 101], 'Mecânico A', 'Travões'),
            $this->log(['id' => 2, 'user_id' => 11, 'workshop_intervention_id' => 101], 'Mecânico B', 'Travões'),
            $this->log(['id' => 3, 'workshop_intervention_id' => 102, 'finished_at' => null, 'duration_minutes' => null, 'started_at' => '2026-11-18 11:30:00'], 'Mecânico A', 'Pneus'),
            $this->log(['id' => 4, 'duration_minutes' => 15]),
        ]);

        $this->assertCount(3, $review['interventions']);
        $tasks = collect($review['interventions'])->keyBy('workshop_intervention_id');
        $this->assertSame('Travões', $tasks[101]['title']);
        $this->assertCount(2, $tasks[101]['mechanics']);
        $this->assertSame(120, $tasks[101]['totals']['closed_minutes']);
        $this->assertSame(30, $tasks[102]['totals']['open_minutes']);
        foreach (['closed_minutes', 'open_minutes', 'open_count', 'unconfirmed_count'] as $key) {
            $this->assertSame($review['totals'][$key], array_sum(array_column(array_column($review['interventions'], 'totals'), $key)));
            $this->assertSame($review['totals'][$key], array_sum(array_column(array_column($review['mechanics'], 'totals'), $key)));
        }
    }

    public function test_fixed_observation_time_is_repeatable_and_never_mutates_logs(): void
    {
        $log = $this->log(['finished_at' => null, 'duration_minutes' => null]);
        $before = $log->getAttributes();
        $first = $this->review([$log]);
        $again = $this->review([$log]);
        $later = $this->review([$log], '2026-11-18 12:10:00');

        $this->assertSame($first, $again);
        $this->assertSame(180, $first['totals']['open_minutes']);
        $this->assertSame(190, $later['totals']['open_minutes']);
        $this->assertSame(0, $later['totals']['closed_minutes']);
        $this->assertSame($before, $log->getAttributes());
        $this->assertFalse($log->isDirty());
    }

    public function test_missing_related_user_and_intervention_keep_the_time_visible(): void
    {
        $review = $this->review([$this->log(['workshop_intervention_id' => 999], null)]);

        $this->assertSame(60, $review['totals']['closed_minutes']);
        $this->assertNotEmpty($review['mechanics'][0]['name']);
        $this->assertNotEmpty($review['interventions'][0]['title']);
    }
}
