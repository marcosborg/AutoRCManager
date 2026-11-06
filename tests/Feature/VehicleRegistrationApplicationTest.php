<?php

namespace Tests\Feature;

use App\Models\{CalendarTask, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleRegistrationApplicationTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCUMENT = 'registration_application';

    private function actor(array $permissions = ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Registration ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(array_fill_keys(array_keys(VehicleDocuments::checks()), 1) + [
            'license' => uniqid('REG'), 'vehicle_registration_document' => 0,
        ]);
    }

    private function file(Vehicle $vehicle, string $type)
    {
        return $vehicle->media()->create(['collection_name' => 'additional_documents', 'name' => 'Registration proof',
            'file_name' => 'registration-proof.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [],
            'custom_properties' => ['document_classification' => ['type' => $type]],
            'generated_conversions' => [], 'responsive_images' => []]);
    }

    public function test_registration_application_is_optional_and_distinct_from_dua(): void
    {
        $this->actingAs($this->actor());
        $vehicle = $this->vehicle();
        $this->assertArrayNotHasKey(self::DOCUMENT, VehicleDocuments::checks());
        $this->assertSame([], VehicleDocuments::pendingChecks($vehicle));
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]))
            ->assertOk()->assertSee('Requerimento de Registo Automóvel');
        $html = view('admin.vehicles.partials.document-summary', compact('vehicle'))->render();
        $this->assertStringContainsString('Acompanhar requerimento de registo automóvel', $html);
        $this->assertStringNotContainsString('data-document="registration_application"', $html);
        $this->assertSame(0, $vehicle->documentFollowups()->count());
        $this->assertSame(0, CalendarTask::where('vehicle_id', $vehicle->id)->count());
        $file = $this->file($vehicle, 'unknown');
        $this->patchJson(route('admin.vehicles.documents.type', [$vehicle, $file->id]), [
            'type' => self::DOCUMENT, 'revision' => VehicleDocuments::revision($file),
            'reason' => 'Identificar requerimento recebido',
        ])->assertOk();
        $file->refresh();
        $this->assertTrue(VehicleDocuments::matches($file, self::DOCUMENT));
        $this->assertFalse(VehicleDocuments::matches($file, 'vehicle_ownership_title'));
        $this->assertSame(0, VehicleDocuments::count($vehicle->fresh(), 'dua'));
        $this->assertSame(0, (int) $vehicle->fresh()->vehicle_registration_document);
    }

    public function test_manual_followup_requires_correct_file_without_marking_registration_complete(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = $this->vehicle();
        $before = $vehicle->fresh()->getAttributes();
        $url = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $data = ['action' => 'contact', 'version' => 0, 'assigned_to_id' => $user->id,
            'contact' => 'Vendedor', 'requested_on' => '2026-11-06', 'next_contact_on' => '2026-11-12', 'note' => 'Pedir requerimento'];
        $this->post($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $followup = $vehicle->documentFollowups()->firstOrFail();
        $task = CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->firstOrFail();
        $this->assertSame('2026-11-12', $task->getRawOriginal('due_date'));
        $html = view('admin.vehicles.partials.document-summary', ['vehicle' => $vehicle->fresh()])->render();
        $this->assertStringContainsString('data-document="registration_application"', $html);
        $this->assertStringContainsString('não substitui o DUA nem confirma o registo concluído', $html);
        $complete = array_replace($data, ['action' => 'complete', 'version' => 1, 'confirmed' => 1]);
        foreach (['dua', 'dua_inspection', 'other'] as $wrongType) {
            $this->postJson($url, $complete + ['media_id' => $this->file($vehicle, $wrongType)->id])->assertUnprocessable();
        }
        $this->postJson($url, $complete + ['media_id' => $this->file($this->vehicle(), self::DOCUMENT)->id])->assertUnprocessable();
        $proof = $this->file($vehicle, self::DOCUMENT);
        $this->post($url, $complete + ['media_id' => $proof->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('completed', $followup->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
    }

    public function test_existing_document_permissions_are_required(): void
    {
        $this->actingAs($this->actor(['vehicle_access', 'vehicle_edit']));
        $vehicle = $this->vehicle();
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]))->assertForbidden();
        $this->post(route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]), [])->assertForbidden();
    }
}
