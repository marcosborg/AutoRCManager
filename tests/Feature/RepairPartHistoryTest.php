<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Repair;
use App\Models\RepairPart;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RepairPartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RepairPartHistoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_edit_keeps_identity_and_records_before_after_and_actor(): void
    {
        $repair = $this->repair();
        $part = $repair->parts()->create(['part_name' => 'Filtro', 'amount' => '20.00', 'part_date' => '2026-10-08']);
        $untouched = $repair->parts()->create(['part_name' => 'Óleo', 'amount' => '30.00']);
        $count = RepairPart::withTrashed()->count();
        $user = $this->user();
        $rows = [$this->row($part, ['amount' => '25.50']), $this->row($untouched)];
        $this->actingAs($user)->put(route('admin.repairs.update', $repair), $this->payload($repair, $rows))->assertSessionHasNoErrors();
        $this->assertSame($count, RepairPart::withTrashed()->count());
        $this->assertEqualsCanonicalizing([$part->id, $untouched->id], $repair->parts()->pluck('id')->all());
        $this->assertEquals(55.5, $repair->parts()->sum('amount'));
        $this->assertSame('2026-10-08', $part->fresh()->part_date->format('Y-m-d'));
        $entry = AuditLog::where('description', 'repair_part:changed')->where('subject_id', $part->id)->firstOrFail();
        $this->assertEquals($user->id, $entry->user_id);
        $this->assertSame('20.00', $entry->properties['before']['amount']);
        $this->assertSame('25.50', $entry->properties['after']['amount']);
        $this->get(route('admin.repairs.edit', $repair))->assertOk()->assertSee('Histórico das peças')->assertSee($user->name)->assertSee('25.50');
        $historyCount = AuditLog::where('description', 'repair_part:changed')->count();
        $this->put(route('admin.repairs.update', $repair), $this->payload($repair, $rows))->assertSessionHasNoErrors();
        $this->assertSame($historyCount, AuditLog::where('description', 'repair_part:changed')->count());
    }

    public function test_missing_parts_payload_preserves_existing_lines(): void
    {
        $repair = $this->repair();
        $part = $repair->parts()->create(['part_name' => 'Filtro']);
        $this->actingAs($this->user())->put(route('admin.repairs.update', $repair), ['vehicle_id' => $repair->vehicle_id])->assertSessionHasNoErrors();
        $this->assertSame([$part->id], $repair->parts()->pluck('id')->all());
    }

    public function test_stale_page_is_rejected_without_changing_repair_or_parts(): void
    {
        $repair = $this->repair();
        $part = $repair->parts()->create(['part_name' => 'Filtro', 'amount' => '10.00']);
        $payload = $this->payload($repair, [$this->row($part, ['amount' => '99.00'])]) + ['obs_1' => 'Stale repair update'];
        $part->update(['amount' => '15.00']);
        $this->actingAs($this->user())->put(route('admin.repairs.update', $repair), $payload)->assertSessionHasErrors('repair_parts');
        $this->assertEquals(15, $part->fresh()->amount);
        $this->assertNotSame('Stale repair update', $repair->fresh()->obs_1);
    }

    public function test_foreign_duplicate_and_unversioned_rows_are_rejected(): void
    {
        $repair = $this->repair();
        $part = $repair->parts()->create(['part_name' => 'Own']);
        $foreign = $this->repair()->parts()->create(['part_name' => 'Other']);
        $this->actingAs($this->user())->put(route('admin.repairs.update', $repair), $this->payload($repair, [$this->row($foreign)]))->assertSessionHasErrors('repair_parts');
        $this->put(route('admin.repairs.update', $repair), $this->payload($repair, [$this->row($part), $this->row($part)]))->assertSessionHasErrors('repair_parts.0.id');
        $this->put(route('admin.repairs.update', $repair), ['vehicle_id' => $repair->vehicle_id, 'repair_parts' => [$this->row($part)]])->assertSessionHasErrors('repair_parts');
        $this->assertSame([$part->id], $repair->parts()->pluck('id')->all());
        $this->assertSame('Other', $foreign->fresh()->part_name);
    }

    public function test_add_and_remove_are_individual_and_removal_keeps_audit(): void
    {
        $repair = $this->repair();
        $part = $repair->parts()->create(['part_name' => 'Old', 'amount' => '40.00']);
        $this->actingAs($this->user())->put(route('admin.repairs.update', $repair), $this->payload($repair, [['part_name' => 'New', 'amount' => '12.00']]))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('repair_parts', ['id' => $part->id]);
        $this->assertEquals(12, $repair->parts()->sum('amount'));
        $audit = AuditLog::where('description', 'repair_part:changed')->where('subject_id', $part->id)->firstOrFail();
        $this->assertSame('Remoção', $audit->properties['operation']);
        $this->assertSame('40.00', $audit->properties['before']['amount']);
    }

    public function test_unauthorized_users_cannot_change_parts(): void
    {
        $repair = $this->repair();
        $this->actingAs(User::factory()->create())->put(route('admin.repairs.update', $repair), $this->payload($repair, [['part_name' => 'Blocked']]))->assertForbidden();
        $this->assertSame(0, $repair->parts()->count());
    }

    private function repair(): Repair
    {
        return Repair::create(['vehicle_id' => Vehicle::create(['license' => uniqid('PART-')])->id]);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Part editor ')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'repair_edit'])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function payload(Repair $repair, array $rows): array
    {
        return ['vehicle_id' => $repair->vehicle_id, 'repair_parts' => $rows, 'repair_parts_revision' => app(RepairPartService::class)->revision($repair)];
    }

    private function row(RepairPart $part, array $changes = []): array
    {
        return array_merge($part->only(['id', 'supplier', 'invoice_number', 'part_name', 'amount']), ['part_date' => $part->part_date?->format('Y-m-d')], $changes);
    }
}
