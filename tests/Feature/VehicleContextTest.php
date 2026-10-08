<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\OperationalUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleConsignment;
use App\Services\VehicleContextService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleContextTest extends TestCase
{
    use DatabaseTransactions;

    public function test_current_location_excludes_past_and_future_periods_without_changing_history(): void
    {
        $this->travelTo(now()->startOfSecond());
        $vehicle = $this->vehicle();
        $vehicle->locations()->create(['operational_unit_id' => $this->unit('Anterior')->id, 'starts_at' => now()->subDays(2), 'ends_at' => now()]);
        $vehicle->locations()->create(['operational_unit_id' => $this->unit('Atual')->id, 'starts_at' => now(), 'ends_at' => now()->addDay()]);
        $vehicle->locations()->create(['operational_unit_id' => $this->unit('Futura')->id, 'starts_at' => now()->addDay()]);
        $before = $vehicle->locations()->get()->toJson();

        $context = app(VehicleContextService::class)->forVehicle($vehicle);

        $this->assertSame('Atual', $context['location']);
        $this->assertFalse($context['location_conflict']);
        $this->assertSame($before, $vehicle->locations()->get()->toJson());
    }

    public function test_missing_location_is_not_inferred_from_purchase_or_storage_fields(): void
    {
        $vehicle = $this->vehicle();
        $vehicle->update(['our_registration' => 'Fornecedor', 'storage_location' => 'Parque antigo']);
        $context = app(VehicleContextService::class)->forVehicle($vehicle);
        $this->assertSame('Por confirmar', $context['location']);
        $this->assertSame('Parque antigo', $context['storage_location']);
        $this->assertFalse($context['location_conflict']);
    }

    public function test_overlapping_locations_require_confirmation(): void
    {
        $vehicle = $this->vehicle();
        foreach (['Parque A', 'Parque B'] as $name) {
            $vehicle->locations()->create(['operational_unit_id' => $this->unit($name)->id, 'starts_at' => now()->subDay()]);
        }
        $context = app(VehicleContextService::class)->forVehicle($vehicle);
        $this->assertSame('Por confirmar', $context['location']);
        $this->assertTrue($context['location_conflict']);
    }

    public function test_free_consignment_destination_is_shown_separately_from_location(): void
    {
        $vehicle = $this->vehicle();
        $vehicle->consignments()->create(['from_unit_id' => $this->unit('Origem')->id, 'to_unit_name' => 'Destino particular', 'starts_at' => now()->subDay(), 'status' => VehicleConsignment::STATUS_ACTIVE]);
        $context = app(VehicleContextService::class)->forVehicle($vehicle);
        $this->assertSame('Destino particular', $context['consignment_destination']);
        $this->assertSame('Por confirmar', $context['location']);
    }

    public function test_future_and_closed_consignments_are_not_shown_as_current(): void
    {
        $vehicle = $this->vehicle();
        $origin = $this->unit('Origem');
        $vehicle->consignments()->create(['from_unit_id' => $origin->id, 'to_unit_name' => 'Futura', 'starts_at' => now()->addDay(), 'status' => VehicleConsignment::STATUS_ACTIVE]);
        $vehicle->consignments()->create(['from_unit_id' => $origin->id, 'to_unit_name' => 'Antiga', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay(), 'status' => VehicleConsignment::STATUS_CLOSED]);
        $this->assertNull(app(VehicleContextService::class)->forVehicle($vehicle)['consignment_destination']);
    }

    public function test_summary_is_available_in_authorized_pages_and_does_not_grant_access(): void
    {
        $vehicle = $this->vehicle();
        $vehicle->locations()->create(['operational_unit_id' => $this->unit('Parque de teste')->id, 'starts_at' => now()->subDay()]);
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.vehicles.show', $vehicle))->assertForbidden();
        $this->actingAs($user)->get(route('admin.vehicles.edit', $vehicle))->assertForbidden();
        $role = Role::create(['title' => uniqid('Context test ')]);
        foreach (['vehicle_show', 'vehicle_edit'] as $title) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $title])->id);
        }
        $user->roles()->attach($role);
        $user->unsetRelation('roles');
        foreach (['show', 'edit'] as $action) {
            $this->actingAs($user->fresh())->get(route('admin.vehicles.'.$action, $vehicle))
                ->assertOk()->assertSee('Situação e localização')->assertSee('Parque de teste');
        }
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(['license' => uniqid('CTX-'), 'general_state_id' => GeneralState::firstOrCreate(['name' => 'CONTEXT TEST'])->id]);
    }

    private function unit(string $name): OperationalUnit
    {
        return OperationalUnit::create(['name' => $name, 'code' => uniqid('CTX-'), 'is_internal' => true]);
    }
}
