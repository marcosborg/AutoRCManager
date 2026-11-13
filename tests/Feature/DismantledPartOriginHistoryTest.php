<?php

namespace Tests\Feature;

use App\Models\{DismantledPart, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DismantledPartOriginHistoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_archiving_origin_keeps_part_searchable_and_identifiable_without_changing_inventory(): void
    {
        $role = Role::create(['title' => uniqid('Origin history ')]);
        foreach (['repair_access', 'repair_edit'] as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission]));
        }
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user);
        $vehicle = Vehicle::create(['foreign_license' => 'SOURCE-'.uniqid()]);
        $part = DismantledPart::create(['vehicle_id' => $vehicle->id, 'description' => 'Alternador de origem',
            'condition' => 'untested', 'location' => 'Prateleira A', 'value' => '45.00',
            'created_by_id' => $user->id, 'submission_token' => (string) \Illuminate\Support\Str::uuid()]);
        $before = $part->fresh()->getAttributes();
        $vehicle->delete();

        $this->assertSame($vehicle->foreign_license, $part->fresh()->vehicle->foreign_license);
        $this->get(route('admin.dismantled-parts.index', ['search' => $vehicle->foreign_license]))
            ->assertOk()->assertSee($vehicle->foreign_license)->assertSee('Alternador de origem')->assertSee('Origem arquivada')
            ->assertViewHas('parts', fn ($parts) => $parts->contains('id', $part->id));
        $this->get(route('admin.dismantled-parts.history', $part))->assertOk()
            ->assertSee($vehicle->foreign_license)->assertSee('Origem arquivada')->assertSee('Ficha #'.$vehicle->id);
        $this->assertSame($before, $part->fresh()->getAttributes());
        $this->assertTrue(Vehicle::withTrashed()->findOrFail($vehicle->id)->trashed());
        $this->postJson(route('admin.dismantled-parts.store'), ['vehicle_id' => $vehicle->id,
            'description' => 'Nova peça', 'condition' => 'good', 'location' => 'A',
            'submission_token' => (string) \Illuminate\Support\Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');

        $this->actingAs(User::factory()->create())->get(route('admin.dismantled-parts.history', $part))->assertForbidden();
    }

    public function test_origin_filter_keeps_conditions_and_excludes_other_vehicles(): void
    {
        $role = Role::create(['title' => uniqid('Origin filter ')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'repair_access']));
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user);
        $origin = Vehicle::create(['foreign_license' => 'ORIGIN-FILTER']);
        $other = Vehicle::create(['foreign_license' => 'OTHER-FILTER']);
        $create = fn ($vehicle, $condition) => DismantledPart::create(['vehicle_id' => $vehicle->id,
            'description' => 'Peça teste', 'condition' => $condition, 'location' => 'A',
            'created_by_id' => $user->id, 'submission_token' => (string) \Illuminate\Support\Str::uuid()]);
        $wanted = $create($origin, 'good');
        $create($origin, 'untested');
        $create($other, 'good');
        $origin->delete();
        $this->get(route('admin.dismantled-parts.index', ['vehicle_id' => $origin->id, 'condition' => 'good']))
            ->assertOk()->assertSee('ORIGIN-FILTER')->assertSee('Origem arquivada')
            ->assertSee('name="vehicle_id"', false)
            ->assertViewHas('parts', fn ($parts) => $parts->pluck('id')->all() === [$wanted->id]);
        $this->getJson(route('admin.dismantled-parts.index', ['vehicle_id' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
        $this->getJson(route('admin.dismantled-parts.index', ['vehicle_id' => 2147483647]))
            ->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
        $this->getJson(route('admin.dismantled-parts.index', ['search' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson(route('admin.dismantled-parts.index', ['search' => str_repeat('x', 256)]))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
    }
}
