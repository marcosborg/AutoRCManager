<?php
namespace Tests\Feature;

use App\Models\{ExternalService, Permission, Role, Suplier, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExternalServiceAmountRangeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ranges_combine_with_existing_filters_without_writes(): void
    {
        $role = Role::create(['title' => uniqid('Range ')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'external_service_access'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        $this->actingAs($user);
        $vehicle = Vehicle::create(['license' => uniqid('EXT')]);
        $supplier = Suplier::create(['name' => uniqid('Supplier ')]);
        $base = ['vehicle_id' => $vehicle->id, 'suplier_id' => $supplier->id, 'description' => 'Teste',
            'status' => 'scheduled', 'priority' => 'normal', 'expected_date' => '2026-12-01'];
        $ids = [];
        foreach ([null, 0, 10.49, 10.50, 20.75, 20.76] as $amount) $ids[] = ExternalService::create($base + ['amount' => $amount])->id;
        ExternalService::create(array_replace($base, ['amount' => 15, 'status' => 'cancelled']));
        $deleted = ExternalService::create($base + ['amount' => 15]);
        $deleted->delete();
        $before = ExternalService::whereIn('id', $ids)->get()->toArray();
        $params = ['vehicle_search' => $vehicle->license, 'suplier_id' => $supplier->id, 'status' => 'scheduled'];
        $check = function ($range, $expected) use ($params) {
            $this->get(route('admin.external-services.index', $params + $range))->assertOk()
                ->assertViewHas('externalServices', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === $expected);
        };
        $check(['amount_min' => '10,50', 'amount_max' => '20,75'], [$ids[3], $ids[4]]);
        $check(['amount_max' => '0'], [$ids[1]]);
        $check(['amount_min' => '20,75'], [$ids[4], $ids[5]]);
        $check([], $ids);
        $this->assertSame($before, ExternalService::whereIn('id', $ids)->get()->toArray());
        foreach ([['amount_min' => '-1'], ['amount_max' => 'abc'], ['amount_min' => '20', 'amount_max' => '10']] as $invalid) {
            $this->getJson(route('admin.external-services.index', $invalid))->assertUnprocessable();
        }
        $user->roles()->detach();
        $this->actingAs($user->fresh())->get(route('admin.external-services.index', ['amount_min' => 0]))->assertForbidden();
    }
}
