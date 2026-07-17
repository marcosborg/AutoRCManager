<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleIucMonthRequirementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stand_roles_must_supply_iuc_month_when_creating_and_editing_vehicles(): void
    {
        $state = GeneralState::query()->firstOrCreate(['name' => 'IUC TEST']);

        foreach (['Stand', 'Stand Adm'] as $roleTitle) {
            $user = $this->userWithRole($roleTitle, ['vehicle_create', 'vehicle_edit']);

            $this->actingAs($user)
                ->post(route('admin.vehicles.store'), ['general_state_id' => $state->id])
                ->assertSessionHasErrors('mes_iuc');

            $this->actingAs($user)
                ->post(route('admin.vehicles.store'), [
                    'general_state_id' => $state->id,
                    'mes_iuc' => 'Julho',
                ])
                ->assertSessionHasNoErrors();

            $vehicle = Vehicle::query()->where('mes_iuc', 'Julho')->latest('id')->firstOrFail();

            $this->actingAs($user)
                ->put(route('admin.vehicles.update', $vehicle), ['general_state_id' => $state->id])
                ->assertSessionHasErrors('mes_iuc');
        }
    }

    public function test_iuc_month_remains_optional_for_other_roles(): void
    {
        $state = GeneralState::query()->firstOrCreate(['name' => 'IUC OPTIONAL TEST']);
        $user = $this->userWithRole('IUC optional role '.uniqid(), ['vehicle_create', 'vehicle_edit']);

        $response = $this->actingAs($user)->post(route('admin.vehicles.store'), [
            'general_state_id' => $state->id,
        ]);

        $response->assertSessionHasNoErrors();
        $vehicle = Vehicle::query()->latest('id')->firstOrFail();
        $this->assertNull($vehicle->mes_iuc);

        $this->actingAs($user)
            ->put(route('admin.vehicles.update', $vehicle), ['general_state_id' => $state->id])
            ->assertSessionHasNoErrors();
    }

    public function test_vehicle_forms_visually_mark_iuc_month_as_required_only_for_stand_roles(): void
    {
        $state = GeneralState::query()->firstOrCreate(['name' => 'IUC FORM TEST']);
        $vehicle = Vehicle::query()->create(['general_state_id' => $state->id]);
        $stand = $this->userWithRole('Stand', ['vehicle_create', 'vehicle_edit']);
        $other = $this->userWithRole('IUC form role '.uniqid(), ['vehicle_create', 'vehicle_edit']);

        $this->actingAs($stand)->get(route('admin.vehicles.create'))
            ->assertOk()
            ->assertSee('id="mes_iuc" required', false);
        $this->actingAs($stand)->get(route('admin.vehicles.edit', $vehicle))
            ->assertOk()
            ->assertSee('id="mes_iuc" required', false);
        $this->actingAs($other)->get(route('admin.vehicles.create'))
            ->assertOk()
            ->assertDontSee('id="mes_iuc" required', false);
    }

    public function test_create_form_explains_validation_failures(): void
    {
        $stand = $this->userWithRole('Stand', ['vehicle_create']);

        $this->actingAs($stand)
            ->from(route('admin.vehicles.create'))
            ->post(route('admin.vehicles.store'), [])
            ->assertRedirect(route('admin.vehicles.create'))
            ->assertSessionHasErrors(['general_state_id', 'mes_iuc']);

        $this->actingAs($stand)
            ->get(route('admin.vehicles.create'))
            ->assertOk()
            ->assertSee('Não foi possível criar a viatura.')
            ->assertSee('has-error', false);
    }

    private function userWithRole(string $roleTitle, array $permissionTitles): User
    {
        $user = User::query()->create([
            'name' => $roleTitle.' IUC test',
            'email' => uniqid('iuc-', true).'@example.test',
            'password' => 'password',
        ]);
        $role = Role::query()->firstOrCreate(['title' => $roleTitle]);
        $permissions = collect($permissionTitles)->map(
            fn (string $title) => Permission::query()->firstOrCreate(['title' => $title])
        );
        $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));
        $user->roles()->attach($role);

        return $user;
    }
}
