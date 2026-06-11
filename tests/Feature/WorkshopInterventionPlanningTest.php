<?php

namespace Tests\Feature;

use App\Models\Repair;
use App\Models\RepairWorkLog;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkshopIntervention;
use App\Models\WorkshopInterventionType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopInterventionPlanningTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manager_can_plan_daily_and_weekly_work_for_multiple_mechanics(): void
    {
        $manager = $this->userWithRole('Chefe oficina');
        $mechanics = collect([$this->userWithRole('Mecânico'), $this->userWithRole('Mecânico')]);
        $repair = Repair::query()->firstOrFail();
        $type = WorkshopInterventionType::where('name', 'Reparação')->firstOrFail();

        $this->actingAs($manager)->post(route('admin.workshop-interventions.store'), [
            'repair_id' => $repair->id, 'type_id' => $type->id, 'title' => 'Revisão geral',
            'planned_start_date' => '2026-06-15', 'planned_end_date' => '2026-06-19',
            'status' => 'planned', 'mechanic_ids' => $mechanics->pluck('id')->all(),
        ])->assertRedirect(route('admin.workshop-interventions.index'));

        $item = WorkshopIntervention::where('title', 'Revisão geral')->firstOrFail();
        $this->assertSame(2, $item->mechanics()->count());
        $this->assertSame('2026-06-15', $item->planned_start_date->format('Y-m-d'));
        $this->assertSame('2026-06-19', $item->planned_end_date->format('Y-m-d'));
    }

    public function test_planning_requires_a_mechanic_and_valid_date_range(): void
    {
        $manager = $this->userWithRole('Chefe oficina');
        $repair = Repair::query()->firstOrFail();
        $type = WorkshopInterventionType::query()->firstOrFail();

        $this->actingAs($manager)->from(route('admin.workshop-interventions.create'))->post(route('admin.workshop-interventions.store'), [
            'repair_id' => $repair->id, 'type_id' => $type->id, 'title' => 'Datas inválidas',
            'planned_start_date' => '2026-06-20', 'planned_end_date' => '2026-06-19',
            'status' => 'planned', 'mechanic_ids' => [],
        ])->assertSessionHasErrors(['planned_end_date', 'mechanic_ids']);
    }

    public function test_mechanic_api_only_lists_assigned_work(): void
    {
        $mechanic = $this->userWithRole('Mecânico');
        $other = $this->userWithRole('Mecânico');
        $assigned = $this->makeIntervention([$mechanic->id], 'Meu trabalho');
        $this->makeIntervention([$other->id], 'Trabalho de outro');

        $response = $this->actingAs($mechanic, 'sanctum')->getJson('/api/mobile/workshop/planning/my-agenda?start_date=2026-06-01&end_date=2026-06-30');

        $response->assertOk()->assertJsonPath('data.0.id', $assigned->id)->assertJsonCount(1, 'data');
    }

    public function test_mechanic_can_only_run_one_timer_and_completion_closes_team_timers_without_closing_repair(): void
    {
        $first = $this->userWithRole('Mecânico');
        $second = $this->userWithRole('Mecânico');
        $item = $this->makeIntervention([$first->id, $second->id], 'Trabalho em equipa');
        $other = $this->makeIntervention([$first->id], 'Outro trabalho');
        $repairFinishedAtBefore = $item->repair->getRawOriginal('repair_finished_at');

        $this->actingAs($first, 'sanctum')->postJson("/api/mobile/workshop/planning/interventions/{$item->id}/start")->assertOk();
        $this->actingAs($first, 'sanctum')->postJson("/api/mobile/workshop/planning/interventions/{$other->id}/start")->assertStatus(422);
        $this->actingAs($second, 'sanctum')->postJson("/api/mobile/workshop/planning/interventions/{$item->id}/start")->assertOk();
        $this->actingAs($first, 'sanctum')->postJson("/api/mobile/workshop/planning/interventions/{$item->id}/complete")->assertOk();

        $this->assertSame('completed', $item->fresh()->status);
        $this->assertSame(0, RepairWorkLog::where('workshop_intervention_id', $item->id)->whereNull('finished_at')->count());
        $this->assertSame($repairFinishedAtBefore, $item->repair->fresh()->getRawOriginal('repair_finished_at'));
    }

    public function test_used_type_cannot_be_deleted(): void
    {
        $manager = $this->userWithRole('Chefe oficina');
        $item = $this->makeIntervention([$this->userWithRole('Mecânico')->id], 'Usa tipo');

        $this->actingAs($manager)->delete(route('admin.workshop-intervention-types.destroy', $item->type_id))
            ->assertSessionHasErrors('type');
        $this->assertDatabaseHas('workshop_intervention_types', ['id' => $item->type_id]);
    }

    private function makeIntervention(array $mechanicIds, string $title): WorkshopIntervention
    {
        $item = WorkshopIntervention::create([
            'repair_id' => Repair::query()->firstOrFail()->id,
            'type_id' => WorkshopInterventionType::query()->firstOrFail()->id,
            'title' => $title, 'planned_start_date' => '2026-06-11', 'planned_end_date' => '2026-06-18', 'status' => 'planned',
        ]);
        $item->mechanics()->sync($mechanicIds);

        return $item;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('title', $role)->firstOrFail());

        return $user;
    }
}
