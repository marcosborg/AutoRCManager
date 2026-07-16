<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\Role;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeletedVehicleManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_deleted_vehicle_can_be_viewed_and_edited_without_being_restored(): void
    {
        $admin = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $state = GeneralState::firstOrFail();
        $vehicle = Vehicle::create([
            'general_state_id' => $state->id,
            'license' => 'ZZ11YY',
            'model' => 'Modelo antes da edição',
        ]);
        $vehicle->delete();

        $this->actingAs($admin)
            ->get(route('admin.vehicles.deleted.show', $vehicle->id))
            ->assertOk()
            ->assertSee('ZZ-11-YY');

        $this->actingAs($admin)
            ->get(route('admin.vehicles.deleted.edit', $vehicle->id))
            ->assertOk()
            ->assertSee('Viatura eliminada.');

        $this->actingAs($admin)
            ->put(route('admin.vehicles.deleted.update', $vehicle->id), [
                'general_state_id' => $state->id,
                'model' => 'Modelo corrigido',
            ])
            ->assertRedirect();

        $updatedVehicle = Vehicle::onlyTrashed()->findOrFail($vehicle->id);
        $this->assertSame('Modelo corrigido', $updatedVehicle->model);
        $this->assertNotNull($updatedVehicle->deleted_at);
    }
}
