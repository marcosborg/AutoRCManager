<?php

namespace Tests\Feature;

use App\Models\CalendarTask;
use App\Models\OperationalAlertRecipient;
use App\Models\Repair;
use App\Models\Role;
use App\Models\Suplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImportProcess;
use App\Services\VehicleImportProcessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImportAdjudicationManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_vehicle_uses_existing_suppliers_as_purchasing_companies(): void
    {
        $admin = $this->userWithRole('Admin');
        $supplier = Suplier::create([
            'name' => 'Fornecedor Comprador Teste',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.vehicles.create'))
            ->assertOk()
            ->assertSee($supplier->name)
            ->assertSee(route('admin.supliers.create'))
            ->assertDontSee('Nova empresa compradora');
    }

    public function test_adjudication_vehicle_page_shows_and_saves_the_import_process_tab(): void
    {
        $admin = $this->userWithRole('Admin');
        $vehicle = $this->adjudicationVehicle('FR-PAGE-01');

        $this->actingAs($admin)
            ->get(route('admin.vehicles.edit', $vehicle))
            ->assertOk()
            ->assertSee('Importação / Adjudicação')
            ->assertSee('Empresa compradora');

        $this->actingAs($admin)
            ->from(route('admin.vehicles.edit', $vehicle))
            ->put(route('admin.vehicles.update', $vehicle), [
                'general_state_id' => 5,
                'our_registration' => 'ARC',
                'import_process_present' => 1,
                'import_decision' => VehicleImportProcess::DECISION_LEGALIZE,
                'import_decision_at' => '2026-07-14T10:00',
                'import_agency_documents_sent' => 0,
                'import_documents_received' => 0,
                'import_new_license_received' => 0,
                'import_scrapped' => 0,
            ])
            ->assertRedirect(route('admin.vehicles.edit', $vehicle));

        $this->assertDatabaseHas('vehicle_import_processes', [
            'vehicle_id' => $vehicle->id,
            'decision' => VehicleImportProcess::DECISION_LEGALIZE,
        ]);
        $this->assertSame('ARC', $vehicle->fresh()->our_registration);
    }

    public function test_legalization_creates_a_twenty_day_management_deadline_without_duplicates(): void
    {
        Carbon::setTestNow('2026-07-14 10:00:00');
        $admin = $this->userWithRole('Admin');
        $vehicle = $this->adjudicationVehicle('FR-LEGAL-01');

        $payload = $this->legalizationPayload();
        $service = app(VehicleImportProcessService::class);
        $process = $service->sync($vehicle, $payload, $admin);
        $service->sync($vehicle->fresh(), $payload, $admin);

        $this->assertSame('2026-08-03', $process->deadline_at->format('Y-m-d'));
        $this->assertSame(1, CalendarTask::where('vehicle_id', $vehicle->id)
            ->where('type', CalendarTask::TYPE_IMPORT_DEADLINE)
            ->whereNull('completed_at')
            ->count());
        $this->assertDatabaseHas('calendar_tasks', [
            'vehicle_id' => $vehicle->id,
            'recipient_group' => CalendarTask::GROUP_MANAGEMENT,
            'type' => CalendarTask::TYPE_IMPORT_DEADLINE,
        ]);
    }

    public function test_scrapping_creates_a_ten_day_deadline_and_completes_it_with_the_milestone(): void
    {
        Carbon::setTestNow('2026-07-14 10:00:00');
        $admin = $this->userWithRole('Admin');
        $vehicle = $this->adjudicationVehicle('FR-SCRAP-01');
        $service = app(VehicleImportProcessService::class);

        $process = $service->sync($vehicle, [
            'import_process_present' => 1,
            'import_decision' => VehicleImportProcess::DECISION_SCRAP,
            'import_decision_at' => '2026-07-14T10:00',
            'import_scrapped' => 0,
        ], $admin);

        $this->assertSame('2026-07-24', $process->deadline_at->format('Y-m-d'));

        $service->sync($vehicle->fresh(), [
            'import_process_present' => 1,
            'import_decision' => VehicleImportProcess::DECISION_SCRAP,
            'import_decision_at' => '2026-07-14T10:00',
            'import_scrapped' => 1,
            'import_scrapped_at' => '2026-07-18T12:00',
        ], $admin);

        $this->assertNotNull(CalendarTask::where('vehicle_id', $vehicle->id)
            ->where('type', CalendarTask::TYPE_IMPORT_DEADLINE)
            ->firstOrFail()
            ->completed_at);
    }

    public function test_receiving_documents_with_an_open_repair_creates_one_workshop_task(): void
    {
        Carbon::setTestNow('2026-07-14 10:00:00');
        $admin = $this->userWithRole('Admin');
        $vehicle = $this->adjudicationVehicle('FR-IPO-01');
        Repair::create(['vehicle_id' => $vehicle->id]);
        $service = app(VehicleImportProcessService::class);

        $payload = array_merge($this->legalizationPayload(), [
            'import_documents_received' => 1,
            'import_documents_received_at' => '2026-07-15T09:30',
        ]);
        $service->sync($vehicle, $payload, $admin);
        $service->sync($vehicle->fresh(), $payload, $admin);

        $this->assertSame(1, CalendarTask::where('vehicle_id', $vehicle->id)
            ->where('type', CalendarTask::TYPE_IPO_DOCUMENTS_READY)
            ->count());
        $this->assertDatabaseHas('calendar_tasks', [
            'vehicle_id' => $vehicle->id,
            'recipient_group' => CalendarTask::GROUP_WORKSHOP,
            'type' => CalendarTask::TYPE_IPO_DOCUMENTS_READY,
        ]);
    }

    public function test_receiving_documents_without_an_open_repair_does_not_create_a_workshop_task(): void
    {
        $admin = $this->userWithRole('Admin');
        $vehicle = $this->adjudicationVehicle('FR-NO-IPO');

        app(VehicleImportProcessService::class)->sync($vehicle, array_merge($this->legalizationPayload(), [
            'import_documents_received' => 1,
            'import_documents_received_at' => '2026-07-15T09:30',
        ]), $admin);

        $this->assertDatabaseMissing('calendar_tasks', [
            'vehicle_id' => $vehicle->id,
            'type' => CalendarTask::TYPE_IPO_DOCUMENTS_READY,
        ]);
    }

    public function test_decision_cannot_change_after_a_specific_milestone_is_recorded(): void
    {
        $admin = $this->userWithRole('Admin');
        $vehicle = $this->adjudicationVehicle('FR-LOCKED');
        $service = app(VehicleImportProcessService::class);

        $service->sync($vehicle, array_merge($this->legalizationPayload(), [
            'import_agency_documents_sent' => 1,
            'import_agency_documents_sent_at' => '2026-07-15T09:30',
        ]), $admin);

        try {
            $service->sync($vehicle->fresh(), [
                'import_process_present' => 1,
                'import_decision' => VehicleImportProcess::DECISION_SCRAP,
                'import_decision_at' => '2026-07-16T10:00',
                'import_scrapped' => 0,
            ], $admin);
            $this->fail('Era esperada uma falha de validação.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('import_decision', $exception->errors());
        }

        $this->assertSame(VehicleImportProcess::DECISION_LEGALIZE, $vehicle->import_process()->firstOrFail()->decision);
    }

    public function test_new_license_updates_the_vehicle_and_creates_workshop_and_tolls_tasks(): void
    {
        Carbon::setTestNow('2026-07-14 10:00:00');
        $admin = $this->userWithRole('Admin');
        $firstAlertUser = $this->userWithRole('Gestão');
        $secondAlertUser = $this->userWithRole('Aux. gestão');
        $vehicle = $this->adjudicationVehicle('FR-NEW-01');
        $alertConfiguration = OperationalAlertRecipient::where('key', OperationalAlertRecipient::KEY_TOLLS)->firstOrFail();
        $alertConfiguration->users()->sync([$firstAlertUser->id, $secondAlertUser->id]);
        $alertConfiguration->update(['user_id' => $firstAlertUser->id]);

        $payload = array_merge($this->legalizationPayload(), [
            'import_new_license_received' => 1,
            'import_new_license' => 'ZZ99XY',
            'import_new_license_received_at' => '2026-07-20T14:00',
        ]);

        app(VehicleImportProcessService::class)->sync($vehicle, $payload, $admin);

        $this->assertSame('ZZ-99-XY', $vehicle->fresh()->license);
        $this->assertSame('FR-NEW-01', $vehicle->fresh()->foreign_license);
        $this->assertDatabaseHas('calendar_tasks', [
            'vehicle_id' => $vehicle->id,
            'type' => CalendarTask::TYPE_NEW_LICENSE_WORKSHOP,
            'recipient_group' => CalendarTask::GROUP_WORKSHOP,
        ]);
        $this->assertDatabaseHas('calendar_tasks', [
            'vehicle_id' => $vehicle->id,
            'type' => CalendarTask::TYPE_NEW_LICENSE_TOLLS,
            'assigned_to_id' => $firstAlertUser->id,
        ]);
        $this->assertDatabaseHas('calendar_tasks', [
            'vehicle_id' => $vehicle->id,
            'type' => CalendarTask::TYPE_NEW_LICENSE_TOLLS,
            'assigned_to_id' => $secondAlertUser->id,
        ]);
        $this->assertSame(2, CalendarTask::where('vehicle_id', $vehicle->id)
            ->where('type', CalendarTask::TYPE_NEW_LICENSE_TOLLS)
            ->count());
        $this->assertNotNull(CalendarTask::where('vehicle_id', $vehicle->id)
            ->where('type', CalendarTask::TYPE_IMPORT_DEADLINE)
            ->firstOrFail()
            ->completed_at);
    }

    public function test_new_license_requires_a_configured_tolls_recipient(): void
    {
        $admin = $this->userWithRole('Admin');
        $vehicle = $this->adjudicationVehicle('FR-NO-TOLLS');
        $alertConfiguration = OperationalAlertRecipient::where('key', OperationalAlertRecipient::KEY_TOLLS)->firstOrFail();
        $alertConfiguration->users()->detach();
        $alertConfiguration->update(['user_id' => null]);

        try {
            app(VehicleImportProcessService::class)->sync($vehicle, array_merge($this->legalizationPayload(), [
                'import_new_license_received' => 1,
                'import_new_license' => 'ZZ88XY',
                'import_new_license_received_at' => '2026-07-20T14:00',
            ]), $admin);
            $this->fail('Era esperada uma falha de validação.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('import_new_license_received_at', $exception->errors());
        }

        $this->assertDatabaseMissing('vehicle_import_processes', ['vehicle_id' => $vehicle->id]);
    }

    public function test_operational_tasks_are_visible_only_to_their_audience_or_assignee(): void
    {
        $management = $this->userWithRole('Gestão');
        $workshop = $this->userWithRole('Chefe oficina');
        $stand = $this->userWithRole('Stand');
        $vehicle = $this->adjudicationVehicle('FR-VISIBILITY');

        $managementTask = CalendarTask::create([
            'title' => 'Prazo gestão',
            'due_date' => Carbon::parse('2026-07-20')->format(config('panel.date_format')),
            'vehicle_id' => $vehicle->id,
            'recipient_group' => CalendarTask::GROUP_MANAGEMENT,
            'type' => CalendarTask::TYPE_IMPORT_DEADLINE,
            'dedupe_key' => 'test-management-'.$vehicle->id,
        ]);
        $workshopTask = CalendarTask::create([
            'title' => 'Alerta oficina',
            'due_date' => Carbon::parse('2026-07-20')->format(config('panel.date_format')),
            'vehicle_id' => $vehicle->id,
            'recipient_group' => CalendarTask::GROUP_WORKSHOP,
            'type' => CalendarTask::TYPE_IPO_DOCUMENTS_READY,
            'dedupe_key' => 'test-workshop-'.$vehicle->id,
        ]);

        $this->assertTrue(CalendarTask::visibleTo($management)->whereKey($managementTask)->exists());
        $this->assertFalse(CalendarTask::visibleTo($management)->whereKey($workshopTask)->exists());
        $this->assertTrue(CalendarTask::visibleTo($workshop)->whereKey($workshopTask)->exists());
        $this->assertFalse(CalendarTask::visibleTo($stand)->whereIn('id', [$managementTask->id, $workshopTask->id])->exists());
    }

    private function legalizationPayload(): array
    {
        return [
            'import_process_present' => 1,
            'import_decision' => VehicleImportProcess::DECISION_LEGALIZE,
            'import_decision_at' => '2026-07-14T10:00',
            'import_agency_documents_sent' => 0,
            'import_documents_received' => 0,
            'import_new_license_received' => 0,
        ];
    }

    private function adjudicationVehicle(string $foreignLicense): Vehicle
    {
        return Vehicle::create([
            'general_state_id' => 5,
            'foreign_license' => $foreignLicense,
            'model' => 'Teste Importação',
        ]);
    }

    private function userWithRole(string $title): User
    {
        $role = Role::where('title', $title)->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user;
    }
}
