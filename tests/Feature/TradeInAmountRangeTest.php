<?php
namespace Tests\Feature;

use App\Models\{Permission, Role, User, VehicleTradeIn};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TradeInAmountRangeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ranges_preserve_filters_permissions_and_records(): void
    {
        $role = Role::create(['title' => uniqid('Range')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'vehicle_trade_in_convert'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        $this->actingAs($user);
        $prefix = uniqid('RANGE');
        $ids = [];
        foreach ([0, 10.49, 10.50, 20.75, 20.76] as $i => $amount) {
            $ids[] = VehicleTradeIn::create(['license' => $prefix.$i, 'normalized_license' => $prefix.$i,
                'amount' => $amount, 'status' => 'pending', 'created_by_id' => $user->id])->id;
        }
        $before = VehicleTradeIn::whereIn('id', $ids)->get()->toArray();
        $params = ['status' => 'pending', 'search' => $prefix, 'date_start' => now()->toDateString(), 'date_end' => now()->toDateString()];
        $check = function ($range, $expected) use ($params) {
            $this->get(route('admin.vehicle-trade-ins.index', $params + $range))->assertOk()
                ->assertViewHas('tradeIns', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === $expected);
        };
        $check(['amount_min' => '10,50', 'amount_max' => '20,75'], [$ids[2], $ids[3]]);
        $check(['amount_max' => '0'], [$ids[0]]);
        $check(['amount_min' => '20,75'], [$ids[3], $ids[4]]);
        $check([], $ids);
        $this->get(route('admin.vehicle-trade-ins.index', array_replace($params, ['date_end' => now()->subDay()->toDateString()])))->assertViewHas('tradeIns', fn ($rows) => $rows->isEmpty());
        $this->assertSame($before, VehicleTradeIn::whereIn('id', $ids)->get()->toArray());
        foreach ([['amount_min' => '-1'], ['amount_max' => 'abc'], ['amount_min' => 20, 'amount_max' => 10]] as $invalid) {
            $this->getJson(route('admin.vehicle-trade-ins.index', $params + $invalid))->assertUnprocessable();
        }
        $role->permissions()->sync([Permission::firstOrCreate(['title' => 'vehicle_trade_in_access'])->id]);
        $this->actingAs($user->fresh())->get(route('admin.vehicle-trade-ins.index', $params))->assertForbidden();
        $user->roles()->detach();
        $this->actingAs($user->fresh())->get(route('admin.vehicle-trade-ins.index', ['status' => 'converted']))->assertForbidden();
    }
}
