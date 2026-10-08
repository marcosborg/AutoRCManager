<?php

namespace Tests\Feature;

use App\Domain\Consignments\ConsignmentStatus;
use App\Models\OperationalUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Repair;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleConsignment;
use App\Models\VehicleConsignmentAudit;
use App\Models\VehicleLocation;
use App\Services\VehicleConsignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VehicleConsignmentManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stand_user_can_open_create_form_and_create_consignment(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand', [
            'vehicle_consignment_access',
            'vehicle_consignment_show',
            'vehicle_consignment_create',
            'vehicle_consignment_edit',
            'vehicle_consignment_delete',
        ]);
        [$vehicle, $from, $to] = $this->consignmentData();

        $this->actingAs($user)
            ->get(route('admin.vehicle-consignments.create'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('admin.vehicle-consignments.store'), [
                'vehicle_id' => $vehicle->id,
                'from_unit_id' => $from->id,
                'to_unit_id' => $to->id,
                'to_unit_name' => null,
                'starts_at' => '2026-07-01 10:00:00',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicle_consignments', [
            'vehicle_id' => $vehicle->id,
            'status' => ConsignmentStatus::ACTIVE,
            'reference_value' => 0,
        ]);
    }

    public function test_stand_adm_can_edit_all_fields_and_close_active_consignment(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand Adm', ['vehicle_consignment_edit']);
        [$vehicle, $from, $to] = $this->consignmentData();
        $otherVehicle = Vehicle::query()->create(['license' => '92-ZZ-92']);
        $otherTo = $this->unit('Destino B');
        $consignment = app(VehicleConsignmentService::class)->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);

        $this->actingAs($user)
            ->put(route('admin.vehicle-consignments.update', $consignment), [
                'vehicle_id' => $otherVehicle->id,
                'from_unit_id' => $to->id,
                'to_unit_id' => $otherTo->id,
                'to_unit_name' => null,
                'starts_at' => '2026-07-02 11:00:00',
                'ends_at' => '2026-07-03 12:00:00',
                'status' => ConsignmentStatus::CLOSED,
            ])
            ->assertSessionHasNoErrors();

        $consignment->refresh();
        $this->assertSame($otherVehicle->id, $consignment->vehicle_id);
        $this->assertSame($otherTo->id, $consignment->to_unit_id);
        $this->assertSame(ConsignmentStatus::CLOSED, $consignment->status);
        $this->assertDatabaseMissing('vehicle_locations', [
            'vehicle_id' => $vehicle->id,
            'operational_unit_id' => $to->id,
        ]);
        $this->assertDatabaseHas('vehicle_locations', [
            'vehicle_id' => $otherVehicle->id,
            'operational_unit_id' => $otherTo->id,
            'ends_at' => '2026-07-03 12:00:00',
        ]);
    }

    public function test_closed_consignment_can_be_corrected_but_not_reopened(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand', ['vehicle_consignment_edit']);
        [$vehicle, $from, $to] = $this->consignmentData();
        $service = app(VehicleConsignmentService::class);
        $consignment = $service->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);
        $service->closeConsignment($consignment, ['ends_at' => '2026-07-02 10:00:00']);

        $payload = [
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => null,
            'to_unit_name' => 'Cliente particular',
            'starts_at' => '2026-07-01 11:00:00',
            'ends_at' => '2026-07-02 12:00:00',
            'status' => ConsignmentStatus::CLOSED,
        ];
        $this->actingAs($user)->put(route('admin.vehicle-consignments.update', $consignment), $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame('Cliente particular', $consignment->fresh()->to_unit_name);

        $payload['status'] = ConsignmentStatus::ACTIVE;
        $payload['ends_at'] = null;
        $this->actingAs($user)
            ->from(route('admin.vehicle-consignments.edit', $consignment))
            ->put(route('admin.vehicle-consignments.update', $consignment), $payload)
            ->assertSessionHasErrors('status');
    }

    public function test_deleting_active_consignment_restores_previous_location(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand', ['vehicle_consignment_delete']);
        [$vehicle, $from, $to] = $this->consignmentData();
        $previous = VehicleLocation::query()->create([
            'vehicle_id' => $vehicle->id,
            'operational_unit_id' => $from->id,
            'starts_at' => '2026-06-01 10:00:00',
        ]);
        $consignment = app(VehicleConsignmentService::class)->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.vehicle-consignments.destroy', $consignment))
            ->assertRedirect(route('admin.vehicle-consignments.index'));

        $this->assertDatabaseMissing('vehicle_consignments', ['id' => $consignment->id]);
        $this->assertNull($previous->fresh()->ends_at);
        $this->assertDatabaseMissing('vehicle_locations', [
            'vehicle_id' => $vehicle->id,
            'operational_unit_id' => $to->id,
        ]);
    }

    public function test_deleting_closed_consignment_preserves_later_location(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand Adm', ['vehicle_consignment_delete']);
        [$vehicle, $from, $to] = $this->consignmentData();
        $laterUnit = $this->unit('Local posterior');
        $previous = VehicleLocation::query()->create([
            'vehicle_id' => $vehicle->id,
            'operational_unit_id' => $from->id,
            'starts_at' => '2026-06-01 10:00:00',
        ]);
        $service = app(VehicleConsignmentService::class);
        $consignment = $service->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);
        $service->closeConsignment($consignment, ['ends_at' => '2026-07-02 10:00:00']);
        $later = VehicleLocation::query()->create([
            'vehicle_id' => $vehicle->id,
            'operational_unit_id' => $laterUnit->id,
            'starts_at' => '2026-07-03 10:00:00',
        ]);

        $this->actingAs($user)->delete(route('admin.vehicle-consignments.destroy', $consignment));

        $this->assertSame('2026-07-03 10:00:00', $previous->fresh()->ends_at);
        $this->assertDatabaseHas('vehicle_locations', ['id' => $later->id, 'ends_at' => null]);
    }

    public function test_user_without_permissions_cannot_create_edit_or_delete(): void
    {
        $user = $this->userWithRoleAndPermissions('User', []);
        [$vehicle, $from, $to] = $this->consignmentData();
        $consignment = VehicleConsignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
            'status' => ConsignmentStatus::ACTIVE,
        ]);

        $this->actingAs($user)->get(route('admin.vehicle-consignments.create'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.vehicle-consignments.edit', $consignment))->assertForbidden();
        $payload = [
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
            'status' => ConsignmentStatus::ACTIVE,
        ];
        $this->actingAs($user)->post(route('admin.vehicle-consignments.store'), $payload)->assertForbidden();
        $this->actingAs($user)->put(route('admin.vehicle-consignments.update', $consignment), $payload)->assertForbidden();
        $this->actingAs($user)->delete(route('admin.vehicle-consignments.destroy', $consignment))->assertForbidden();
    }

    public function test_consignment_history_records_create_update_and_delete_with_user_and_license(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand', [
            'vehicle_consignment_access',
            'vehicle_consignment_edit',
            'vehicle_consignment_delete',
        ]);
        [$vehicle, $from, $to] = $this->consignmentData();
        $service = app(VehicleConsignmentService::class);

        $this->actingAs($user);
        $consignment = $service->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);
        $service->updateConsignment($consignment, [
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => null,
            'to_unit_name' => 'Destino auditado',
            'starts_at' => '2026-07-01 11:00:00',
            'ends_at' => null,
            'status' => ConsignmentStatus::ACTIVE,
        ]);
        $service->deleteConsignment($consignment);

        $audits = VehicleConsignmentAudit::query()->where('consignment_id', $consignment->id)->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $audits->pluck('action')->all());
        $this->assertSame($vehicle->license, $audits[0]->vehicle_license_after);
        $this->assertSame($vehicle->license, $audits[2]->vehicle_license_before);
        $this->assertSame($user->id, $audits[1]->user_id);
        $this->assertSame('2026-07-01 10:00:00', \Carbon\Carbon::parse($audits[1]->before['starts_at'])->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-01 11:00:00', \Carbon\Carbon::parse($audits[1]->after['starts_at'])->format('Y-m-d H:i:s'));
        $this->assertNotNull($audits[2]->before);
        $this->assertNull($audits[2]->after);
    }

    public function test_consignment_history_can_be_searched_by_license(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand', ['vehicle_consignment_access']);
        [$vehicle, $from, $to] = $this->consignmentData();
        $vehicle->update(['license' => '12-AB-34']);
        app(VehicleConsignmentService::class)->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('admin.vehicle-consignments.history', [
                'license' => '12AB34',
                'occurrence_date' => '2026-07-01',
            ]))
            ->assertOk()
            ->assertSee('12-AB-34')
            ->assertSee('Criada');

        $this->actingAs($user)
            ->get(route('admin.vehicle-consignments.history', [
                'license' => '12AB34',
                'occurrence_date' => '2026-06-01',
            ]))
            ->assertOk()
            ->assertSee('Sem alterações para apresentar.');
    }

    public function test_consignment_with_free_text_destination_defaults_legacy_value_to_zero(): void
    {
        $user = $this->userWithRoleAndPermissions('Stand', ['vehicle_consignment_create']);
        [$vehicle, $from] = $this->consignmentData();

        $this->actingAs($user)->post(route('admin.vehicle-consignments.store'), [
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_name' => 'Destino sem unidade cadastrada',
            'starts_at' => '2026-07-01 10:00:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicle_consignments', [
            'vehicle_id' => $vehicle->id,
            'to_unit_id' => null,
            'to_unit_name' => 'Destino sem unidade cadastrada',
            'reference_value' => 0,
        ]);
        $this->assertDatabaseMissing('vehicle_locations', ['vehicle_id' => $vehicle->id]);
    }

    public function test_edit_preserves_a_nonzero_historical_reference_value(): void
    {
        [$vehicle, $from, $to] = $this->consignmentData();
        $data = [
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ];
        $service = app(VehicleConsignmentService::class);
        $consignment = $service->createConsignment($data);
        DB::table('vehicle_consignments')->where('id', $consignment->id)->update(['reference_value' => 1375.50]);

        $service->updateConsignment($consignment->refresh(), array_merge($data, [
            'to_unit_id' => null,
            'to_unit_name' => 'Novo destino',
            'status' => ConsignmentStatus::ACTIVE,
        ]));

        $this->assertDatabaseHas('vehicle_consignments', ['id' => $consignment->id, 'reference_value' => 1375.50]);
    }

    public function test_closed_consignment_period_cannot_be_overlapped(): void
    {
        [$vehicle, $from, $to] = $this->consignmentData();
        $data = [
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ];
        $service = app(VehicleConsignmentService::class);
        $consignment = $service->createConsignment($data);
        $service->closeConsignment($consignment, ['ends_at' => '2026-07-02 10:00:00']);

        $this->expectException(ValidationException::class);
        $service->createConsignment(array_merge($data, ['starts_at' => '2026-07-01 12:00:00']));
    }

    public function test_consignment_cannot_close_before_it_starts(): void
    {
        [$vehicle, $from, $to] = $this->consignmentData();
        $service = app(VehicleConsignmentService::class);
        $consignment = $service->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);

        $this->expectException(ValidationException::class);
        $service->closeConsignment($consignment, ['ends_at' => '2026-06-30 10:00:00']);
    }

    public function test_consignment_cannot_close_with_an_open_repair(): void
    {
        [$vehicle, $from, $to] = $this->consignmentData();
        $service = app(VehicleConsignmentService::class);
        $consignment = $service->createConsignment([
            'vehicle_id' => $vehicle->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'starts_at' => '2026-07-01 10:00:00',
        ]);
        Repair::create(['vehicle_id' => $vehicle->id]);

        $this->expectException(ValidationException::class);
        $service->closeConsignment($consignment, ['ends_at' => '2026-07-02 10:00:00']);
    }

    private function userWithRoleAndPermissions(string $roleTitle, array $permissions): User
    {
        $user = User::query()->create([
            'name' => 'Consignment test user',
            'email' => uniqid('consignment-', true) . '@example.test',
            'password' => 'password',
        ]);
        $role = Role::query()->create(['title' => uniqid($roleTitle . ' ', true)]);
        $role->permissions()->sync(collect($permissions)->map(
            fn (string $title) => Permission::query()->firstOrCreate(['title' => $title])->id
        ));
        $user->roles()->attach($role);

        return $user;
    }

    private function consignmentData(): array
    {
        return [
            Vehicle::query()->create(['license' => strtoupper(substr(uniqid(), -6))]),
            $this->unit('Origem'),
            $this->unit('Destino'),
        ];
    }

    private function unit(string $name): OperationalUnit
    {
        return OperationalUnit::query()->create([
            'name' => $name,
            'code' => uniqid('unit-', true),
            'is_internal' => true,
        ]);
    }
}
