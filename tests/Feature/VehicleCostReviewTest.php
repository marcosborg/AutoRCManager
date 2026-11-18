<?php

namespace Tests\Feature;

use App\Models\{AccountOperation, AuditLog, Department, ExternalService, PartOrder, PaymentMethod, Permission, Repair, Role, User, Vehicle};
use App\Services\{VehicleCostReviewService, VehicleProfitabilityService};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VehicleCostReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sources_reconcile_with_existing_cost_without_counting_a_shared_order_twice(): void
    {
        $vehicle = $this->vehicle(['purchase_price' => 1000, 'commission' => 75]);
        $repair = Repair::create(['vehicle_id' => $vehicle->id]);
        $part = $repair->parts()->create(['part_name' => 'Filtro', 'amount' => 25]);
        $order = PartOrder::create(['vehicle_id' => $vehicle->id, 'repair_id' => $repair->id, 'status' => 'received']);
        $item = $order->items()->create(['description' => 'Pastilhas', 'quantity' => 1, 'total_final' => 100, 'status' => 'received']);
        $vehicle->generic_payments()->create(['expense_label' => 'Transporte', 'amount' => 30, 'payment_method_id' => PaymentMethod::firstOrCreate(['name' => 'Cost review test'])->id, 'paid_at' => now()->format(config('panel.date_format'))]);
        ExternalService::create(['vehicle_id' => $vehicle->id, 'description' => 'Jante', 'status' => 'completed', 'amount' => 50]);
        ExternalService::create(['vehicle_id' => $vehicle->id, 'description' => 'Cancelado', 'status' => 'cancelled', 'amount' => 900]);
        AccountOperation::create(['vehicle_id' => $vehicle->id, 'department_id' => Department::firstOrCreate(['name' => 'Oficina'])->id, 'movement_type' => 'outcome', 'description' => 'Oficina', 'total' => 40, 'qty' => 1, 'date' => now()->format(config('panel.date_format'))]);
        $profitability = app(VehicleProfitabilityService::class)->build($vehicle->fresh());
        $review = app(VehicleCostReviewService::class)->build($vehicle->fresh());

        $this->assertEquals(1245, $profitability['global_cost']);
        $this->assertSame($profitability['global_cost'], $review['current_total']);
        $this->assertEquals(1245, $review['source_total']);
        $this->assertTrue($review['reconciles']);
        $rows = collect($review['rows']);
        $this->assertCount(1, $rows->where('id', 'order_item_'.$item->id));
        $this->assertNotEmpty($rows->firstWhere('id', 'repair_part_'.$part->id)['source_route']);
        $this->assertContains('repair_order_overlap', array_column($review['warnings'], 'code'));
        $this->assertContains('cash_expense_overlap', array_column($review['warnings'], 'code'));
    }

    public function test_acquisition_omissions_are_visible_without_increasing_the_current_total(): void
    {
        $vehicle = $this->vehicle(['purchase_price' => 1000, 'purchase_vat_value' => 230, 'iuc_price' => 80, 'commission' => 75, 'tow_price' => 50]);
        $review = app(VehicleCostReviewService::class)->build($vehicle);
        $this->assertEquals(1000, $review['current_total']);
        $this->assertEquals(1435, $review['acquisition_total']);
        $rows = collect($review['rows']);
        foreach (['purchase_vat_value' => 230, 'iuc_price' => 80, 'commission' => 75, 'tow_price' => 50] as $field => $amount) {
            $row = $rows->firstWhere('id', 'acquisition_'.$field);
            $this->assertNotNull($row);
            $this->assertFalse($row['included']);
            $this->assertEquals($amount, $row['amount']);
        }
        $this->assertContains('acquisition_fields_omitted', array_column($review['warnings'], 'code'));
        $this->assertTrue($review['reconciles']);
    }

    public function test_order_estimates_and_problem_states_are_explained_without_recalculating_them(): void
    {
        $vehicle = $this->vehicle(['purchase_price' => 1000]);
        $cancelled = PartOrder::create(['vehicle_id' => $vehicle->id, 'status' => 'cancelled']);
        $estimate = $cancelled->items()->create(['description' => 'Estimativa cancelada', 'quantity' => 2, 'total_estimated' => 90, 'status' => 'pending']);
        $order = PartOrder::create(['vehicle_id' => $vehicle->id, 'status' => 'partially_received']);
        $returned = $order->items()->create(['description' => 'Devolvida', 'quantity' => 1, 'total_final' => 50, 'status' => 'returned']);
        $partial = $order->items()->create(['description' => 'Rececao parcial', 'quantity' => 4, 'total_final' => 120, 'status' => 'partially_received']);
        $partial->forceFill(['received_quantity' => 1])->save();
        $review = app(VehicleCostReviewService::class)->build($vehicle->fresh());
        $this->assertEquals(1260, $review['current_total']);
        $rows = collect($review['rows']);
        $this->assertSame('Estimado', $rows->firstWhere('id', 'order_item_'.$estimate->id)['value_status']);
        foreach ([$estimate, $returned, $partial] as $item) {
            $row = $rows->firstWhere('id', 'order_item_'.$item->id);
            $this->assertTrue($row['included']);
            $this->assertNotEmpty($row['notes']);
        }
        $this->assertContains('order_states_included', array_column($review['warnings'], 'code'));
        $this->assertTrue($review['reconciles']);
    }

    public function test_payments_are_informational_and_do_not_turn_partial_payment_into_full_payment(): void
    {
        $vehicle = $this->vehicle(['purchase_price' => 1000]);
        $order = PartOrder::create(['vehicle_id' => $vehicle->id, 'status' => 'received']);
        $order->items()->create(['description' => 'Paid independently', 'quantity' => 1, 'total_final' => 100, 'status' => 'received']);
        $partial = $order->payments()->create(['amount' => 100, 'payment_status' => 'partially_paid']);
        $cancelled = $order->payments()->create(['amount' => 50, 'payment_status' => 'cancelled']);
        $supplier = $vehicle->supplier_payments()->create(['amount' => 200, 'payment_method_id' => PaymentMethod::firstOrCreate(['name' => 'Cost review test'])->id, 'paid_at' => now()->format(config('panel.date_format'))]);
        $review = app(VehicleCostReviewService::class)->build($vehicle->fresh());
        $this->assertEquals(1100, $review['current_total']);
        $this->assertEquals(1100, $review['source_total']);
        $payments = collect($review['payment_rows']);
        $this->assertCount(3, $payments);
        $this->assertFalse($payments->contains(fn ($row) => $row['included']));
        $this->assertSame('Parcialmente pago', $payments->firstWhere('id', 'part_payment_'.$partial->id)['payment_status']);
        $this->assertSame('Cancelado', $payments->firstWhere('id', 'part_payment_'.$cancelled->id)['payment_status']);
        $this->assertEquals(200, $payments->firstWhere('id', 'supplier_payment_'.$supplier->id)['amount']);
    }

    public function test_empty_vehicle_has_no_invented_costs_or_duplicate_warnings(): void
    {
        $review = app(VehicleCostReviewService::class)->build($this->vehicle(['purchase_price' => 0]));
        $this->assertEquals(0, $review['current_total']);
        $this->assertEquals(0, $review['source_total']);
        $this->assertTrue($review['reconciles']);
        $this->assertSame([], $review['payment_rows']);
        $this->assertSame([], $review['warnings']);
    }

    public function test_missing_amounts_are_not_presented_as_confirmed_zero(): void
    {
        $vehicle = $this->vehicle(['purchase_price' => null]);
        $repair = Repair::create(['vehicle_id' => $vehicle->id]);
        $part = $repair->parts()->create(['part_name' => 'Without price', 'amount' => null]);
        $service = ExternalService::create(['vehicle_id' => $vehicle->id, 'description' => 'Without agreed value', 'status' => 'requested', 'amount' => null]);
        $review = app(VehicleCostReviewService::class)->build($vehicle->fresh());
        $this->assertEquals(0, $review['current_total']);
        $this->assertEquals(0, $review['source_total']);
        $this->assertTrue($review['reconciles']);
        $rows = collect($review['rows']);
        foreach (['purchase_origin', 'repair_part_'.$part->id, 'external_service_'.$service->id] as $id) {
            $row = $rows->firstWhere('id', $id);
            $this->assertSame('Por confirmar', $row['value_status']);
            $this->assertNotEmpty($row['notes']);
        }
        $this->assertContains('missing_source_values', array_column($review['warnings'], 'code'));
    }

    public function test_reading_twice_preserves_stored_values_and_audit_history(): void
    {
        $vehicle = $this->vehicle(['purchase_price' => 1000, 'commission' => 75]);
        $repair = Repair::create(['vehicle_id' => $vehicle->id]);
        $repair->parts()->create(['part_name' => 'Original', 'amount' => 40]);
        $before = [DB::table('vehicles')->where('id', $vehicle->id)->first(), DB::table('repair_parts')->where('repair_id', $repair->id)->get()->all(), AuditLog::count()];
        $service = app(VehicleCostReviewService::class);
        $first = $service->build($vehicle->fresh());
        $second = $service->build($vehicle->fresh());
        $after = [DB::table('vehicles')->where('id', $vehicle->id)->first(), DB::table('repair_parts')->where('repair_id', $repair->id)->get()->all(), AuditLog::count()];
        $this->assertEquals($before, $after);
        $this->assertSame($first, $second);
    }

    public function test_review_respects_existing_financial_and_workshop_access(): void
    {
        $vehicle = $this->vehicle(['purchase_price' => 1000, 'commission' => 75]);
        $this->actingAs($this->user('Adm', ['vehicle_edit', 'financial_sensitive_access']))
            ->get(route('admin.vehicles.edit', $vehicle))->assertOk()->assertSee('Conferência dos custos');
        foreach (['Cost review ordinary', 'Aux. oficina', 'Chefe oficina'] as $role) {
            $this->actingAs($this->user($role, ['vehicle_edit', 'financial_sensitive_access']))
                ->get(route('admin.vehicles.edit', $vehicle))->assertOk()->assertDontSee('Conferência dos custos');
        }
    }

    private function vehicle(array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge(['license' => uniqid('COST-')], $attributes));
    }

    private function user(string $title, array $abilities): User
    {
        $role = Role::firstOrCreate(['title' => $title]);
        foreach ($abilities as $ability) {
            $role->permissions()->syncWithoutDetaching([Permission::firstOrCreate(['title' => $ability])->id]);
        }
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        return $user;
    }
}
