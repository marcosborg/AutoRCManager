<?php

namespace Tests\Feature;

use App\Models\{CalendarTask, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehiclePermanentCertificateTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCUMENT = 'permanent_certificate';

    private function actor(array $permissions = ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access'], ?string $roleTitle = null): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['title' => $roleTitle ?? uniqid('Certificate ')]);
        foreach ($permissions as $permission) $role->permissions()->syncWithoutDetaching(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(array_fill_keys(array_keys(VehicleDocuments::checks()), 1) + ['license' => uniqid('CERT')]);
    }

    private function file(Vehicle $vehicle, string $type)
    {
        return $vehicle->media()->create(['collection_name' => 'additional_documents', 'name' => 'Certificate',
            'file_name' => 'certificate.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [],
            'custom_properties' => ['document_classification' => ['type' => $type]],
            'generated_conversions' => [], 'responsive_images' => []]);
    }

    public function test_certificate_is_optional_and_distinct_from_citizen_card(): void
    {
        $this->actingAs($this->actor());
        $vehicle = $this->vehicle();
        $this->assertArrayNotHasKey(self::DOCUMENT, VehicleDocuments::checks());
        $this->assertSame([], VehicleDocuments::pendingChecks($vehicle));
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]))->assertOk()->assertSee('Certidão Permanente');
        $html = view('admin.vehicles.partials.document-summary', compact('vehicle'))->render();
        $this->assertStringContainsString('Acompanhar certidão permanente', $html);
        $this->assertStringNotContainsString('data-document="permanent_certificate"', $html);
        $this->assertSame(0, $vehicle->documentFollowups()->count());
        $this->assertSame(0, CalendarTask::where('vehicle_id', $vehicle->id)->count());
        $before = $vehicle->fresh()->getAttributes();
        $file = $this->file($vehicle, 'unknown');
        $this->patchJson(route('admin.vehicles.documents.type', [$vehicle, $file->id]), [
            'type' => self::DOCUMENT, 'revision' => VehicleDocuments::revision($file), 'reason' => 'Identificar certidão recebida',
        ])->assertOk();
        $this->assertTrue(VehicleDocuments::matches($file->fresh(), self::DOCUMENT));
        $this->assertFalse(VehicleDocuments::matches($file->fresh(), 'copy_of_the_citizen_card'));
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
        $html = view('admin.vehicles.partials.document-summary', ['vehicle' => $vehicle->fresh()])->render();
        $this->assertStringContainsString('data-document="permanent_certificate"', $html);
    }

    public function test_manual_followup_requires_own_active_certificate_and_does_not_mutate_vehicle(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = $this->vehicle();
        $before = $vehicle->fresh()->getAttributes();
        $url = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $data = ['action' => 'contact', 'version' => 0, 'assigned_to_id' => $user->id,
            'contact' => 'Fornecedor', 'requested_on' => '2026-11-06', 'next_contact_on' => '2026-11-12', 'note' => 'Pedir certidão'];
        $this->post($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $followup = $vehicle->documentFollowups()->firstOrFail();
        $task = CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->firstOrFail();
        $this->assertSame('2026-11-12', $task->getRawOriginal('due_date'));
        $complete = array_replace($data, ['action' => 'complete', 'version' => 1, 'confirmed' => 1]);
        foreach (['copy_of_the_citizen_card', 'purchase_invoice', 'other'] as $wrongType) {
            $this->postJson($url, $complete + ['media_id' => $this->file($vehicle, $wrongType)->id])->assertUnprocessable();
        }
        $this->postJson($url, $complete + ['media_id' => $this->file($this->vehicle(), self::DOCUMENT)->id])->assertUnprocessable();
        $annulled = $this->file($vehicle, self::DOCUMENT);
        $annulled->setCustomProperty('document_annulment', ['reason' => 'Substituído'])->save();
        $this->postJson($url, $complete + ['media_id' => $annulled->id])->assertUnprocessable();
        $proof = $this->file($vehicle, self::DOCUMENT);
        $this->postJson($url, array_replace($complete, ['confirmed' => 0, 'media_id' => $proof->id]))->assertUnprocessable();
        $this->post($url, $complete + ['media_id' => $proof->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('completed', $followup->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
        $this->postJson($url, $complete + ['media_id' => $proof->id])->assertStatus(409);
        $this->assertSame(1, $vehicle->documentFollowups()->count());
        $this->assertSame(1, CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->count());
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
    }

    public function test_document_access_permission_and_workshop_restriction_remain_required(): void
    {
        $vehicle = $this->vehicle();
        $this->actingAs($this->actor(['vehicle_access', 'vehicle_edit']));
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]))->assertForbidden();
        $this->post(route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]), [])->assertForbidden();
        $this->actingAs($this->actor(['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access'], 'Aux. oficina'));
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]))->assertForbidden();
        $this->post(route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]), [])->assertForbidden();
    }
}
