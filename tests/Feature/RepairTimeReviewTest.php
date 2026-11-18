<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Repair;
use App\Models\RepairWorkLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleProfitabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RepairTimeReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function user(bool $allowed = true): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => $allowed ? 'Time review allowed' : 'Time review denied']);
        if ($allowed) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => 'repair_edit'])->id);
        }
        $user->roles()->attach($role);

        return $user;
    }

    private function repair(): Repair
    {
        $vehicle = Vehicle::create(['license' => 'TEST-TIME-11A', 'purchase_price' => 1250]);

        return Repair::create(['vehicle_id' => $vehicle->id]);
    }

    public function test_authorized_page_separates_closed_and_open_time_without_changing_money_or_history(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(12, 0));
        $repair = $this->repair();
        $user = $this->user();
        $repair->parts()->create(['part_name' => 'Peça de teste', 'amount' => 25]);
        $closed = RepairWorkLog::create([
            'repair_id' => $repair->id, 'user_id' => $user->id,
            'started_at' => now()->subHours(2), 'finished_at' => now()->subHour(), 'duration_minutes' => 60,
        ]);
        $open = RepairWorkLog::create([
            'repair_id' => $repair->id, 'user_id' => $user->id,
            'started_at' => now()->subMinutes(15), 'finished_at' => null, 'duration_minutes' => null,
        ]);
        $beforeMoney = app(VehicleProfitabilityService::class)->build($repair->vehicle->fresh());
        $beforeLogs = RepairWorkLog::where('repair_id', $repair->id)->orderBy('id')->get()->map->getAttributes()->all();
        $beforeRepair = $repair->fresh()->getAttributes();
        $auditCount = AuditLog::count();

        $this->actingAs($user);
        for ($read = 0; $read < 2; $read++) {
            $this->get(route('admin.repairs.edit', $repair))
                ->assertOk()
                ->assertSee('Conferência dos tempos')
                ->assertSee('Trabalho terminado:')
                ->assertSee('Trabalho em curso:')
                ->assertSee('15 min provisórios')
                ->assertSee('Finalizar trabalho (eu)')
                ->assertViewHas('timeReview', fn ($review) => $review['totals']['closed_minutes'] === 60
                    && $review['totals']['open_minutes'] === 15 && $review['totals']['open_count'] === 1)
                ->assertViewHas('totalMechanicMinutes', 75);
        }

        $this->assertSame(1275.0, $beforeMoney['global_cost']);
        $this->assertSame($beforeMoney, app(VehicleProfitabilityService::class)->build($repair->vehicle->fresh()));
        $this->assertSame($beforeLogs, RepairWorkLog::where('repair_id', $repair->id)->orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame($beforeRepair, $repair->fresh()->getAttributes());
        $this->assertSame($auditCount, AuditLog::count());
        $this->assertNull($open->fresh()->finished_at);
        $this->assertEquals(60, $closed->fresh()->duration_minutes);
        $this->travelBack();
    }

    public function test_empty_repair_has_explicit_empty_review(): void
    {
        $repair = $this->repair();

        $this->actingAs($this->user())->get(route('admin.repairs.edit', $repair))
            ->assertOk()
            ->assertSee('Conferência dos tempos')
            ->assertSee('Sem tempos registados.')
            ->assertViewHas('timeReview', fn ($review) => $review['totals']['closed_minutes'] === 0
                && $review['totals']['open_count'] === 0);
    }

    public function test_user_without_repair_edit_cannot_read_the_review(): void
    {
        $repair = $this->repair();

        $this->actingAs($this->user(false))->get(route('admin.repairs.edit', $repair))
            ->assertForbidden()
            ->assertDontSee('Conferência dos tempos');
    }
}
