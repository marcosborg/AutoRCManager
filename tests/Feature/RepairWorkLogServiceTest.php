<?php

namespace Tests\Feature;

use App\Models\Repair;
use App\Models\RepairWorkLog;
use App\Models\User;
use App\Services\RepairWorkLogService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RepairWorkLogServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_different_mechanics_can_work_on_the_same_repair_and_names_are_deduplicated(): void
    {
        $repair = Repair::query()->whereNull('repair_finished_at')->firstOrFail();
        [$first, $second] = User::factory()->count(2)->create();
        $service = app(RepairWorkLogService::class);

        $service->start($repair, $first);
        $service->start($repair, $first);
        $service->start($repair, $second);

        $this->assertSame(2, RepairWorkLog::where('repair_id', $repair->id)->whereNull('finished_at')->count());
    }

    public function test_mechanic_cannot_start_a_second_active_job(): void
    {
        $repairs = Repair::query()->whereNull('repair_finished_at')->limit(2)->get();
        $this->assertCount(2, $repairs);
        $mechanic = User::factory()->create();
        $service = app(RepairWorkLogService::class);
        $service->start($repairs[0], $mechanic);

        $this->expectException(ValidationException::class);
        $service->start($repairs[1], $mechanic);
    }

    public function test_closing_repair_finishes_all_its_logs_without_touching_other_repairs(): void
    {
        $repairs = Repair::query()->limit(2)->get();
        $this->assertCount(2, $repairs);
        [$first, $second, $other] = User::factory()->count(3)->create();

        $targetLogs = collect([$first, $second])->map(fn (User $user) => RepairWorkLog::create([
            'repair_id' => $repairs[0]->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(10),
        ]));
        $otherLog = RepairWorkLog::create([
            'repair_id' => $repairs[1]->id,
            'user_id' => $other->id,
            'started_at' => now()->subMinutes(10),
        ]);

        app(RepairWorkLogService::class)->closeForRepair($repairs[0]);

        $targetLogs->each(fn (RepairWorkLog $log) => $this->assertNotNull($log->fresh()->finished_at));
        $this->assertNull($otherLog->fresh()->finished_at);
    }
}
