<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Suplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleContextService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleDestinationCompanyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_each_destination_is_independent_of_supplier_and_legacy_value(): void
    {
        $user = $this->user(['vehicle_create']);
        $supplier = Suplier::create(['name' => 'Destination test supplier']);
        foreach (Vehicle::DESTINATION_COMPANIES as $code => $label) {
            $license = uniqid('DEST-');
            $this->actingAs($user)->post(route('admin.vehicles.store'), [
                'license' => $license,
                'general_state_id' => $this->state(),
                'destination_company' => $code,
                'suplier_id' => $supplier->id,
                'our_registration' => 'Legacy purchase record',
            ])->assertSessionHasNoErrors();
            $vehicle = Vehicle::where('license', $license)->firstOrFail();
            $this->assertSame($code, $vehicle->destination_company);
            $this->assertEquals($supplier->id, $vehicle->suplier_id);
            $this->assertSame('Legacy purchase record', $vehicle->our_registration);
            $context = app(VehicleContextService::class)->forVehicle($vehicle);
            $this->assertSame($label, $context['destination_company']);
            $this->assertSame($supplier->name, $context['supplier']);
        }
    }

    public function test_edit_and_clear_destination_preserve_legacy_and_location_history(): void
    {
        $vehicle = Vehicle::create(['license' => uniqid('DEST-'), 'general_state_id' => $this->state(), 'our_registration' => 'Freerent']);
        $this->assertSame('Por confirmar', app(VehicleContextService::class)->forVehicle($vehicle)['destination_company']);
        $user = $this->user(['vehicle_edit']);
        $locations = $vehicle->locations()->get()->toJson();
        foreach (['auto_rafael', null] as $company) {
            $this->actingAs($user)->put(route('admin.vehicles.update', $vehicle), [
                'general_state_id' => $vehicle->general_state_id,
                'destination_company' => $company,
            ])->assertSessionHasNoErrors();
            $this->assertSame($company, $vehicle->fresh()->destination_company);
            $this->assertSame('Freerent', $vehicle->fresh()->our_registration);
            $this->assertSame($locations, $vehicle->locations()->get()->toJson());
        }
    }

    public function test_unknown_companies_are_rejected_and_permissions_are_unchanged(): void
    {
        $vehicle = Vehicle::create(['general_state_id' => $this->state()]);
        $payload = ['general_state_id' => $vehicle->general_state_id, 'destination_company' => 'unknown'];
        $this->actingAs($this->user(['vehicle_create', 'vehicle_edit']))
            ->post(route('admin.vehicles.store'), $payload)->assertSessionHasErrors('destination_company');
        $this->put(route('admin.vehicles.update', $vehicle), $payload)->assertSessionHasErrors('destination_company');
        $payload['destination_company'] = 'freerent';
        $this->actingAs($this->user([]))->post(route('admin.vehicles.store'), $payload)->assertForbidden();
        $this->put(route('admin.vehicles.update', $vehicle), $payload)->assertForbidden();
        $this->assertNull($vehicle->fresh()->destination_company);
    }

    public function test_forms_offer_only_group_companies_and_show_legacy_record_separately(): void
    {
        $vehicle = Vehicle::create(['general_state_id' => $this->state(), 'our_registration' => 'OLD SUPPLIER']);
        $user = $this->user(['vehicle_create', 'vehicle_edit']);
        foreach ([route('admin.vehicles.create'), route('admin.vehicles.edit', $vehicle)] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('Empresa de destino')
                ->assertSee('Geração Determinada')->assertSee('Auto Rafael')->assertSee('Freerent');
        }
        $this->get(route('admin.vehicles.edit', $vehicle))->assertSee('OLD SUPPLIER')->assertSee('Registo anterior de empresa/fornecedor');
    }

    private function state(): int
    {
        return GeneralState::firstOrCreate(['name' => 'DESTINATION TEST'])->id;
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Destination test ')]);
        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        }
        $user->roles()->attach($role);
        return $user;
    }
}
