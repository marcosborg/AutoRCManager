<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehiclePurchasingCompanyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_vehicle_can_be_created_with_full_purchasing_company_name(): void
    {
        $state = GeneralState::query()->firstOrCreate(['name' => 'PURCHASING COMPANY TEST']);
        $permission = Permission::query()->firstOrCreate(['title' => 'vehicle_create']);
        $role = Role::query()->firstOrCreate(['title' => 'Purchasing company test role']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user = User::query()->create([
            'name' => 'Purchasing company test user',
            'email' => uniqid('purchasing-company-', true).'@example.test',
            'password' => 'password',
        ]);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->post(route('admin.vehicles.store'), [
            'general_state_id' => $state->id,
            'license' => '99-ZZ-98',
            'our_registration' => 'DUARTE DELGADO',
        ]);

        $vehicle = Vehicle::query()->where('license', '99-ZZ-98')->firstOrFail();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.vehicles.edit', $vehicle));
        $this->assertSame('DUARTE DELGADO', $vehicle->our_registration);
    }
}
