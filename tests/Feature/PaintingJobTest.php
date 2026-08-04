<?php

namespace Tests\Feature;

use App\Models\PaintingJob;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaintingJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_painter_only_lists_and_opens_assigned_jobs(): void
    {
        $painter = $this->userWithRole('Pintor');
        $other = $this->userWithRole('Pintor');
        $assigned = $this->makeJob($painter);
        $hidden = $this->makeJob($other);

        $this->actingAs($painter, 'sanctum')->getJson('/api/mobile/workshop/painting-jobs')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $assigned->id);
        $this->actingAs($painter, 'sanctum')->getJson("/api/mobile/workshop/painting-jobs/{$assigned->id}")
            ->assertOk()->assertJsonCount(15, 'data.damages')->assertJsonCount(9, 'data.materials');
        $this->actingAs($painter, 'sanctum')->getJson("/api/mobile/workshop/painting-jobs/{$hidden->id}")
            ->assertForbidden();
    }

    public function test_assigned_painter_can_update_and_complete_but_cannot_edit_after_completion(): void
    {
        $painter = $this->userWithRole('Pintor');
        $job = $this->makeJob($painter);
        $payload = $this->payload();

        $this->actingAs($painter, 'sanctum')->putJson("/api/mobile/workshop/painting-jobs/{$job->id}", $payload)
            ->assertOk()->assertJsonPath('data.damages.0.intensity', 'medium')->assertJsonPath('data.materials.0.reference', 'TIN-01');
        $this->actingAs($painter, 'sanctum')->postJson("/api/mobile/workshop/painting-jobs/{$job->id}/complete", $payload)
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertNotNull($job->fresh()->completed_at);
        $this->actingAs($painter, 'sanctum')->putJson("/api/mobile/workshop/painting-jobs/{$job->id}", $payload)
            ->assertStatus(422);
    }

    public function test_manager_can_create_assign_and_reopen_a_painting_job(): void
    {
        $manager = $this->userWithRole('Chefe oficina');
        $painter = $this->userWithRole('Pintor');
        $vehicle = Vehicle::with(['brand', 'client'])->firstOrFail();

        $response = $this->actingAs($manager)->post(route('admin.painting-jobs.store'), [
            'vehicle_id' => $vehicle->id, 'painter_id' => $painter->id, 'entry_date' => '2026-08-04', 'notes' => 'Teste',
        ]);
        $job = PaintingJob::latest('id')->firstOrFail();
        $response->assertRedirect(route('admin.painting-jobs.edit', $job));
        $this->assertSame($painter->id, $job->painter_id);
        $this->assertSame(15, $job->damages()->count());
        $this->assertSame(9, $job->materials()->count());
        $this->actingAs($manager)->get(route('admin.painting-jobs.index'))->assertOk()->assertSee($job->license);
        $this->actingAs($manager)->get(route('admin.painting-jobs.show', $job))->assertOk()->assertSee('Danos a tratar');
        $this->actingAs($manager)->get(route('admin.painting-jobs.edit', $job))->assertOk()->assertSee('Materiais');

        $this->actingAs($manager)->post(route('admin.painting-jobs.complete', $job))->assertRedirect();
        $this->assertSame('completed', $job->fresh()->status);
        $this->actingAs($manager)->post(route('admin.painting-jobs.reopen', $job))->assertRedirect();
        $this->assertSame('open', $job->fresh()->status);
    }

    public function test_user_without_painting_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $job = $this->makeJob($this->userWithRole('Pintor'));
        $this->actingAs($user, 'sanctum')->getJson('/api/mobile/workshop/painting-jobs')->assertForbidden();
        $this->actingAs($user)->get(route('admin.painting-jobs.show', $job))->assertForbidden();
    }

    private function makeJob(User $painter): PaintingJob
    {
        $vehicle = Vehicle::firstOrFail();
        $job = PaintingJob::create([
            'vehicle_id' => $vehicle->id, 'painter_id' => $painter->id, 'status' => 'open',
            'license' => $vehicle->license ?: $vehicle->foreign_license, 'brand_model' => $vehicle->model,
            'entry_date' => '2026-08-04',
        ]);
        foreach (PaintingJob::DAMAGE_ZONES as $zone => $label) {
            $job->damages()->create(['zone' => $zone]);
        }
        foreach (PaintingJob::DEFAULT_MATERIALS as $position => $material) {
            $job->materials()->create(['material_type' => $material, 'position' => $position]);
        }

        return $job;
    }

    private function payload(): array
    {
        return [
            'damages' => collect(PaintingJob::DAMAGE_ZONES)->keys()->map(fn ($zone, $index) => ['zone' => $zone, 'intensity' => $index === 0 ? 'medium' : null])->all(),
            'materials' => [['material_type' => 'Tinta', 'reference' => 'TIN-01', 'quantity' => 2, 'used_date' => '2026-08-04', 'hours' => 1.5]],
            'optics' => 'Polir', 'black_parts' => null, 'wheels' => 'Duas', 'other_work' => null, 'notes' => 'Preparada',
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('title', $role)->firstOrFail());

        return $user;
    }
}
