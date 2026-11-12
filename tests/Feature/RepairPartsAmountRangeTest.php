<?php
namespace Tests\Feature;
use App\Models\{Permission, Role, User, RepairPart};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
class RepairPartsAmountRangeTest extends TestCase
{
    use DatabaseTransactions;
    public function test_ranges_filter_rows_and_totals_without_writes(): void
    {
        $role = Role::create(['title' => uniqid('Range')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'repair_access'])->id);
        $user = User::factory()->create(); $user->roles()->sync([$role->id]); $this->actingAs($user);
        $supplier = uniqid('SUP'); $ids = [];
        $repair = \App\Models\Repair::create(['vehicle_id' => \App\Models\Vehicle::create([])->id]);
        foreach ([0, 10.49, 10.50, 20.75, 20.76] as $amount) $ids[] = RepairPart::create(['repair_id' => $repair->id, 'supplier' => $supplier, 'part_name' => 'Teste', 'amount' => $amount, 'part_date' => now()])->id;
        $before = RepairPart::whereIn('id', $ids)->get()->toArray();
        $params = ['supplier' => $supplier, 'amount_min' => '10,50', 'amount_max' => '20,75'];
        $this->get(route('admin.repair-parts-report.index', $params))->assertOk()
            ->assertViewHas('totalLines', 2)->assertViewHas('totalAmount', 31.25)
            ->assertViewHas('parts', fn ($p) => $p->pluck('id')->sort()->values()->all() === [$ids[2], $ids[3]]);
        $this->get(route('admin.repair-parts-report.index', ['supplier' => $supplier, 'amount_max' => 0]))->assertViewHas('totalLines', 1);
        foreach ([['amount_min' => -1], ['amount_max' => 'abc'], ['amount_min' => 20, 'amount_max' => 10]] as $invalid) $this->getJson(route('admin.repair-parts-report.index', $invalid))->assertUnprocessable();
        $this->assertSame($before, RepairPart::whereIn('id', $ids)->get()->toArray());
        $user->roles()->detach(); $this->actingAs($user->fresh())->get(route('admin.repair-parts-report.index'))->assertForbidden();
    }
}
