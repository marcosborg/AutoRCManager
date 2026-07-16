<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\GeneralState;
use App\Models\Permission;
use App\Models\Repair;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkshopState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopStateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sending_a_vehicle_to_workshop_does_not_create_an_intervention(): void
    {
        $user = $this->userWithPermissions(['vehicle_edit']);
        $vehicle = $this->vehicleInState('SALVADOS');
        if ($workshopClient = Client::query()->find(1)) {
            $vehicle->update(['client_id' => $workshopClient->id]);
        }
        $defaultState = $this->defaultWorkshopState();
        $this->generalState('OFICINA');
        $repairCount = Repair::query()->count();

        $this->actingAs($user)
            ->post(route('admin.vehicles.send-to-workshop', $vehicle))
            ->assertRedirect(route('admin.vehicles.edit', $vehicle));

        $vehicle->refresh();
        $this->assertSame(0, strcasecmp('OFICINA', $vehicle->general_state->name));
        $this->assertSame($defaultState->id, $vehicle->workshop_state_id);
        $this->assertSame($repairCount, Repair::query()->count());
    }

    public function test_sending_a_vehicle_to_workshop_is_idempotent(): void
    {
        $user = $this->userWithPermissions(['vehicle_edit']);
        $vehicle = $this->vehicleInState('SALVADOS');
        $this->defaultWorkshopState();
        $this->generalState('OFICINA');

        $this->actingAs($user)->post(route('admin.vehicles.send-to-workshop', $vehicle));
        $this->actingAs($user)->post(route('admin.vehicles.send-to-workshop', $vehicle));

        $this->assertSame(0, $vehicle->repairs()->count());
        $this->assertSame(1, WorkshopState::query()->where('is_default', true)->count());
    }

    public function test_workshop_page_lists_vehicles_without_interventions_and_excludes_vehicles_outside_workshop(): void
    {
        $user = $this->userWithPermissions(['repair_access']);
        $workshopVehicle = $this->vehicleInState('OFICINA', '11-AA-11');
        $outsideVehicle = $this->vehicleInState('SALVADOS', '22-BB-22');
        $workshopVehicle->update(['workshop_state_id' => $this->defaultWorkshopState()->id]);

        $this->actingAs($user)
            ->get(route('admin.repairs.index'))
            ->assertOk()
            ->assertSee('11-AA-11')
            ->assertDontSee('22-BB-22')
            ->assertSee('Sem intervenções');

        $this->assertSame(0, $outsideVehicle->repairs()->count());
    }

    public function test_workshop_page_warns_when_vehicle_needs_a_second_key(): void
    {
        $user = $this->userWithPermissions(['repair_access']);
        $withoutSecondKey = $this->vehicleInState('OFICINA', '33-CC-33');
        $withoutSecondKey->update(['key' => false, 'workshop_state_id' => $this->defaultWorkshopState()->id]);
        $withSecondKey = $this->vehicleInState('OFICINA', '44-DD-44');
        $withSecondKey->update(['key' => true, 'workshop_state_id' => $this->defaultWorkshopState()->id]);

        $this->actingAs($user)
            ->get(route('admin.repairs.index'))
            ->assertOk()
            ->assertSee('Fazer segunda chave')
            ->assertSee('data-second-key-warning="'.$withoutSecondKey->id.'"', false)
            ->assertDontSee('data-second-key-warning="'.$withSecondKey->id.'"', false);
    }

    public function test_workshop_page_offers_vehicle_access_without_starting_an_intervention(): void
    {
        $user = $this->userWithPermissions(['repair_access', 'vehicle_edit']);
        $vehicle = $this->vehicleInState('OFICINA', '55-EE-55');
        $vehicle->update(['workshop_state_id' => $this->defaultWorkshopState()->id]);

        $this->actingAs($user)
            ->get(route('admin.repairs.index'))
            ->assertOk()
            ->assertSee('Abrir viatura')
            ->assertSee(route('admin.vehicles.edit', $vehicle), false);

        $this->assertSame(0, $vehicle->repairs()->count());
    }

    public function test_workshop_user_can_remove_vehicle_and_restore_its_previous_state(): void
    {
        $user = $this->userWithPermissions(['repair_access']);
        $previousState = $this->generalState('SALVADOS');
        $vehicle = $this->vehicleInState('SALVADOS', '66-FF-66');
        $this->generalState('OFICINA');
        $this->defaultWorkshopState();

        $this->actingAs($user)->post(route('admin.vehicles.send-to-workshop', $vehicle));
        $repairCount = $vehicle->repairs()->count();

        $this->actingAs($user)
            ->from(route('admin.repairs.index'))
            ->delete(route('admin.vehicles.workshop.destroy', $vehicle))
            ->assertRedirect(route('admin.repairs.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message', 'Viatura retirada da oficina e reposta no estado anterior.');

        $vehicle->refresh();
        $this->assertSame($previousState->id, $vehicle->general_state_id);
        $this->assertNull($vehicle->workshop_state_id);
        $this->assertSame($repairCount, $vehicle->repairs()->count());
    }

    public function test_workshop_page_shows_remove_button_to_user_with_workshop_access(): void
    {
        $user = $this->userWithPermissions(['repair_access']);
        $vehicle = $this->vehicleInState('SALVADOS', '77-GG-77');
        $this->generalState('OFICINA');
        $this->defaultWorkshopState();
        $this->actingAs($user)->post(route('admin.vehicles.send-to-workshop', $vehicle));

        $this->actingAs($user)
            ->get(route('admin.repairs.index'))
            ->assertOk()
            ->assertSee('Eliminar')
            ->assertSee(route('admin.vehicles.workshop.destroy', $vehicle), false);
    }

    public function test_user_without_workshop_access_cannot_remove_vehicle_from_workshop(): void
    {
        $user = $this->userWithPermissions([]);
        $vehicle = $this->vehicleInState('OFICINA');

        $this->actingAs($user)
            ->delete(route('admin.vehicles.workshop.destroy', $vehicle))
            ->assertForbidden();
    }

    public function test_updating_sold_workshop_state_synchronizes_general_state_without_changing_repairs(): void
    {
        $user = $this->userWithPermissions(['workshop_state_edit']);
        $vehicle = $this->vehicleInState('OFICINA');
        $soldState = WorkshopState::query()->updateOrCreate(
            ['name' => 'Vendidos'],
            ['position' => 2, 'is_active' => true]
        );
        $soldGeneralState = $this->generalState('Vendida');
        $repairCount = $vehicle->repairs()->count();

        $this->actingAs($user)
            ->patch(route('admin.vehicles.workshop-state.update', $vehicle), [
                'workshop_state_id' => $soldState->id,
            ])
            ->assertSessionHasNoErrors();

        $vehicle->refresh();
        $this->assertSame($soldState->id, $vehicle->workshop_state_id);
        $this->assertSame($soldGeneralState->id, $vehicle->general_state_id);
        $this->assertSame($repairCount, $vehicle->repairs()->count());
    }

    public function test_workshop_state_is_updated_when_synchronized_general_state_does_not_exist(): void
    {
        $user = $this->userWithPermissions(['workshop_state_edit']);
        $vehicle = $this->vehicleInState('OFICINA');
        GeneralState::query()->whereRaw('LOWER(name) = ?', ['entregue'])->delete();
        $deliveredState = WorkshopState::query()->updateOrCreate(
            ['name' => 'Entregues'],
            ['position' => 3, 'is_active' => true]
        );

        $this->actingAs($user)
            ->from(route('admin.repairs.index'))
            ->patch(route('admin.vehicles.workshop-state.update', $vehicle), [
                'workshop_state_id' => $deliveredState->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message', 'Estado da Oficina atualizado.');

        $vehicle->refresh();
        $this->assertSame($deliveredState->id, $vehicle->workshop_state_id);
        $this->assertSame(0, strcasecmp('OFICINA', $vehicle->general_state->name));
    }

    public function test_starting_an_intervention_creates_only_one_open_repair(): void
    {
        $user = $this->userWithPermissions(['repair_create', 'repair_edit']);
        $vehicle = $this->vehicleInState('OFICINA');
        $vehicle->update(['kilometers' => 12345, 'workshop_state_id' => $this->defaultWorkshopState()->id]);

        $response = $this->actingAs($user)->post(route('admin.vehicles.start-intervention', $vehicle));
        $repair = $vehicle->repairs()->latest('id')->firstOrFail();

        $response->assertRedirect(route('admin.repairs.edit', $repair));
        $this->assertSame('workshop', $repair->work_type);
        $this->assertSame(12345, $repair->kilometers);

        $this->actingAs($user)
            ->from(route('admin.repairs.index'))
            ->post(route('admin.vehicles.start-intervention', $vehicle))
            ->assertSessionHasErrors('vehicle_id');
        $this->assertSame(1, $vehicle->repairs()->count());
    }

    public function test_default_state_cannot_be_deactivated_and_used_state_is_deactivated_instead_of_deleted(): void
    {
        $user = $this->userWithPermissions(['workshop_state_edit', 'workshop_state_delete']);
        $defaultState = $this->defaultWorkshopState();

        $this->actingAs($user)
            ->put(route('admin.workshop-states.update', $defaultState), [
                'name' => $defaultState->name,
                'position' => 1,
                'is_active' => 0,
                'is_default' => 0,
            ])
            ->assertSessionHasErrors('workshop_state');
        $this->assertTrue($defaultState->fresh()->is_active);

        $usedState = WorkshopState::query()->create([
            'name' => 'Estado em uso',
            'position' => 20,
            'is_active' => true,
        ]);
        $this->vehicleInState('OFICINA')->update(['workshop_state_id' => $usedState->id]);

        $this->actingAs($user)->delete(route('admin.workshop-states.destroy', $usedState));

        $this->assertDatabaseHas('workshop_states', ['id' => $usedState->id, 'is_active' => false]);
    }

    public function test_user_without_workshop_permissions_cannot_manage_states(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('admin.workshop-states.index'))
            ->assertForbidden();
    }

    private function userWithPermissions(array $permissionTitles): User
    {
        $user = User::query()->create([
            'name' => 'Workshop test user',
            'email' => uniqid('workshop-', true).'@example.test',
            'password' => 'password',
        ]);
        $role = Role::query()->create(['title' => uniqid('Workshop role ', true)]);
        $permissions = collect($permissionTitles)->map(
            fn (string $title) => Permission::query()->firstOrCreate(['title' => $title])
        );
        $role->permissions()->sync($permissions->pluck('id'));
        $user->roles()->attach($role);

        return $user;
    }

    private function vehicleInState(string $stateName, ?string $license = null): Vehicle
    {
        return Vehicle::query()->create([
            'license' => $license ?? strtoupper(substr(uniqid(), -2)).'-AA-'.random_int(10, 99),
            'general_state_id' => $this->generalState($stateName)->id,
        ]);
    }

    private function generalState(string $name): GeneralState
    {
        return GeneralState::query()->firstOrCreate(['name' => $name]);
    }

    private function defaultWorkshopState(): WorkshopState
    {
        WorkshopState::query()->where('is_default', true)->update(['is_default' => false]);

        return WorkshopState::query()->updateOrCreate(
            ['name' => 'Viaturas para reparar'],
            ['position' => 1, 'is_active' => true, 'is_default' => true]
        );
    }
}
