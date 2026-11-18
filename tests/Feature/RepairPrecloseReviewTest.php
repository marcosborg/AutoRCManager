<?php

namespace Tests\Feature;

use App\Models\{AuditLog, ExternalService, PartOrder, Permission, Repair, RepairWorkLog, Role, User, Vehicle, WorkshopIntervention, WorkshopInterventionType};
use App\Services\VehicleProfitabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairPrecloseReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $abilities = ['repair_edit', 'part_order_access', 'part_order_show', 'external_service_access', 'external_service_edit', 'workshop_planning_edit']): User
    {
        $role = Role::create(['title' => uniqid('Preclose ')]);
        $role->permissions()->sync(collect($abilities)->map(fn ($title) => Permission::firstOrCreate(['title' => $title])->id));
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    private function repair(?Vehicle $vehicle = null): Repair
    {
        $vehicle ??= Vehicle::create(['license' => uniqid('PRE-'), 'purchase_price' => 1000]);
        return Repair::create(['vehicle_id' => $vehicle->id]);
    }

    private function task(Repair $repair, string $status): WorkshopIntervention
    {
        return WorkshopIntervention::create([
            'repair_id' => $repair->id,
            'type_id' => WorkshopInterventionType::firstOrCreate(['name' => 'Preclose test'])->id,
            'title' => 'Tarefa '.$status,
            'planned_start_date' => today(), 'planned_end_date' => today(), 'status' => $status,
        ]);
    }

    private function order(Repair $repair, ?int $repairId, string $status = 'ordered'): PartOrder
    {
        return PartOrder::create(['vehicle_id' => $repair->vehicle_id, 'repair_id' => $repairId, 'status' => $status]);
    }

    private function service(Repair $repair, string $status = 'requested', bool $invoice = false): ExternalService
    {
        $service = ExternalService::create(['vehicle_id' => $repair->vehicle_id, 'description' => 'Serviço '.$status, 'status' => $status, 'amount' => 25]);
        if ($invoice) {
            $service->media()->create(['collection_name' => 'invoice_file', 'name' => 'Teste', 'file_name' => 'test.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [], 'custom_properties' => [], 'generated_conversions' => [], 'responsive_images' => []]);
        }
        return $service;
    }

    private function review(Repair $repair): array
    {
        return $this->get(route('admin.repairs.edit', $repair))->assertOk()
            ->assertSee('Antes de finalizar')->viewData('precloseReview');
    }

    public function test_empty_review_does_not_claim_validation_or_create_records(): void
    {
        $repair = $this->repair();
        $this->actingAs($this->user());
        $before = AuditLog::count();
        $review = $this->review($repair);
        $this->assertSame(0, $review['time']['open_count']);
        $this->assertSame([], $review['tasks']['pending']);
        $this->assertSame([], $review['tasks']['other']);
        $this->assertSame(0, $review['parts']['total_count']);
        $this->assertSame([], $review['parts']['rows']);
        $this->assertSame([], $review['orders']['repair']);
        $this->assertSame([], $review['orders']['vehicle']);
        $this->assertSame([], $review['services']['rows']);
        $this->assertSame($before, AuditLog::count());
    }

    public function test_time_tasks_and_manual_part_gaps_are_separated_without_changing_totals(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $repair = $this->repair();
        $user = $this->user();
        $this->actingAs($user);
        RepairWorkLog::create(['repair_id' => $repair->id, 'user_id' => $user->id, 'started_at' => now()->subHour(), 'finished_at' => now()->subMinutes(30), 'duration_minutes' => 30]);
        RepairWorkLog::create(['repair_id' => $repair->id, 'user_id' => $user->id, 'started_at' => now()->subMinutes(10)]);
        $tasks = collect(['planned', 'in_progress', 'completed', 'cancelled'])->mapWithKeys(fn ($status) => [$status => $this->task($repair, $status)]);
        $missing = $repair->parts()->create(['part_name' => 'Sem dados', 'amount' => null, 'invoice_number' => '']);
        $complete = $repair->parts()->create(['part_name' => 'Zero registado', 'amount' => 0, 'invoice_number' => 'FT-1']);
        $review = $this->review($repair);
        $this->assertSame(30, $review['time']['closed_minutes']);
        $this->assertSame(10, $review['time']['open_minutes']);
        $this->assertSame(1, $review['time']['open_count']);
        $this->assertEqualsCanonicalizing([$tasks['planned']->id, $tasks['in_progress']->id], array_column($review['tasks']['pending'], 'id'));
        $this->assertEqualsCanonicalizing([$tasks['completed']->id, $tasks['cancelled']->id], array_column($review['tasks']['other'], 'id'));
        $this->assertSame(2, $review['parts']['total_count']);
        $this->assertSame([$missing->id], array_column($review['parts']['rows'], 'id'));
        $this->assertCount(2, $review['parts']['rows'][0]['missing']);
        $this->assertSame(1000.0, app(VehicleProfitabilityService::class)->build($repair->vehicle->fresh())['global_cost']);
        $this->assertSame('0.00', $complete->fresh()->amount);
        $this->travelBack();
    }

    public function test_order_scope_deduplicates_current_repair_and_excludes_other_repair_on_same_vehicle(): void
    {
        $repair = $this->repair();
        $otherRepair = $this->repair($repair->vehicle);
        $current = $this->order($repair, $repair->id);
        $vehicleOnly = $this->order($repair, null);
        $other = $this->order($repair, $otherRepair->id);
        $this->actingAs($this->user());
        $review = $this->review($repair);
        $this->assertSame([$current->id], array_column($review['orders']['repair'], 'id'));
        $this->assertSame([$vehicleOnly->id], array_column($review['orders']['vehicle'], 'id'));
        $ids = array_merge(array_column($review['orders']['repair'], 'id'), array_column($review['orders']['vehicle'], 'id'));
        $this->assertNotContains($other->id, $ids);
        $this->assertCount(2, array_unique($ids));
    }

    public function test_partial_receipts_remain_pending_but_installed_returned_and_cancelled_are_not_new_obligations(): void
    {
        $repair = $this->repair();
        $order = $this->order($repair, $repair->id, 'partially_received');
        $partial = $order->items()->create(['description' => 'Parcial', 'quantity' => 4, 'status' => 'partially_received']);
        $partial->forceFill(['received_quantity' => 1])->save();
        $installed = $order->items()->create(['description' => 'Instalada', 'quantity' => 1, 'status' => 'installed']);
        $returned = $order->items()->create(['description' => 'Devolvida', 'quantity' => 1, 'status' => 'returned']);
        $received = $order->items()->create(['description' => 'Recebida', 'quantity' => 1, 'status' => 'received']);
        $cancelled = $this->order($repair, null, 'cancelled');
        $cancelled->items()->create(['description' => 'Cancelada', 'quantity' => 1, 'status' => 'pending']);
        $this->actingAs($this->user());
        $review = $this->review($repair);
        $row = collect($review['orders']['repair'])->firstWhere('id', $order->id);
        $items = collect($row['items'])->keyBy('id');
        $this->assertTrue($items[$partial->id]['pending']);
        $this->assertEquals(1, $items[$partial->id]['received']);
        $this->assertEquals(4, $items[$partial->id]['quantity']);
        foreach ([$installed, $returned, $received] as $item) $this->assertFalse($items[$item->id]['pending']);
        $cancelRow = collect($review['orders']['vehicle'])->firstWhere('id', $cancelled->id);
        $this->assertFalse($cancelRow['pending']);
        $this->assertFalse($cancelRow['items'][0]['pending']);
    }

    public function test_vehicle_services_show_invoice_evidence_and_cancelled_state_without_changing_costs_or_history(): void
    {
        $repair = $this->repair();
        $withInvoice = $this->service($repair, 'completed', true);
        $withoutInvoice = $this->service($repair);
        $cancelled = $this->service($repair, 'cancelled');
        $this->actingAs($this->user());
        $snapshot = fn () => [
            $repair->fresh()->getAttributes(),
            DB::table('external_services')->where('vehicle_id', $repair->vehicle_id)->orderBy('id')->get()->toJson(),
            $withInvoice->fresh()->media->map->getAttributes()->all(),
            AuditLog::count(),
            app(VehicleProfitabilityService::class)->build($repair->vehicle->fresh()),
        ];
        $before = $snapshot();
        $first = $this->review($repair);
        $second = $this->review($repair);
        $rows = collect($first['services']['rows'])->keyBy('id');
        $this->assertSame('Fatura anexada', $rows[$withInvoice->id]['invoice']);
        $this->assertSame('Fatura por confirmar', $rows[$withoutInvoice->id]['invoice']);
        $this->assertSame('Sem fatura anexada — serviço cancelado.', $rows[$cancelled->id]['invoice']);
        $this->assertFalse($rows[$cancelled->id]['pending']);
        $this->assertEquals($before, $snapshot());
        $this->assertSame($first, $second);
        $this->assertSame(1050.0, $before[4]['global_cost']);
    }

    public function test_access_to_order_and_service_details_and_links_respects_existing_permissions(): void
    {
        $repair = $this->repair();
        $order = $this->order($repair, $repair->id);
        $service = $this->service($repair);
        $this->actingAs($this->user());
        $allowed = $this->review($repair);
        $this->assertNotNull($allowed['orders']['repair'][0]['url']);
        $this->assertNotNull($allowed['services']['rows'][0]['url']);
        $this->actingAs($this->user(['repair_edit', 'part_order_access', 'external_service_access']));
        $readOnly = $this->review($repair);
        $this->assertNull($readOnly['orders']['repair'][0]['url']);
        $this->assertNull($readOnly['services']['rows'][0]['url']);
        $this->actingAs($this->user(['repair_edit']));
        $denied = $this->review($repair);
        $this->assertFalse($denied['orders']['allowed']);
        $this->assertSame([], $denied['orders']['repair']);
        $this->assertSame([], $denied['orders']['vehicle']);
        $this->assertFalse($denied['services']['allowed']);
        $this->assertSame([], $denied['services']['rows']);
        $this->actingAs($this->user([]))->get(route('admin.repairs.edit', $repair))->assertForbidden();
    }

    public function test_informational_pending_items_do_not_block_existing_finish_or_change_financial_cost(): void
    {
        $repair = $this->repair();
        $repair->update(['repair_started_at' => now()->subHour()]);
        $user = $this->user();
        $task = $this->task($repair, 'planned');
        $service = $this->service($repair);
        $repair->parts()->create(['part_name' => 'Por confirmar', 'amount' => null]);
        $log = RepairWorkLog::create(['repair_id' => $repair->id, 'user_id' => $user->id, 'started_at' => now()->subMinutes(10)]);
        $this->actingAs($user);
        $this->review($repair);
        $before = app(VehicleProfitabilityService::class)->build($repair->vehicle->fresh());
        $this->post(route('admin.repairs.finish', $repair))->assertSessionHasNoErrors()->assertRedirect(route('admin.repairs.edit', $repair));
        $this->assertNotNull($repair->fresh()->repair_finished_at);
        $this->assertNotNull($log->fresh()->finished_at);
        $this->assertSame('planned', $task->fresh()->status);
        $this->assertSame('requested', $service->fresh()->status);
        $this->assertSame($before, app(VehicleProfitabilityService::class)->build($repair->vehicle->fresh()));
    }

    public function test_existing_finish_permission_and_started_requirement_are_preserved(): void
    {
        $repair = $this->repair();
        $this->actingAs($this->user([]))->post(route('admin.repairs.finish', $repair))->assertForbidden();
        $this->actingAs($this->user())->post(route('admin.repairs.finish', $repair))->assertSessionHasErrors('repair_started_at');
        $this->assertNull($repair->fresh()->repair_finished_at);
    }
}
