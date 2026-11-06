<?php

namespace Tests\Feature;

use App\Models\{CalendarTask, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleTransportGuideTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCUMENT = 'transport_guide';

    private function actor(array $permissions = ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access'], ?string $roleTitle = null): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['title' => $roleTitle ?? uniqid('Transport guide ')]);
        foreach ($permissions as $permission) $role->permissions()->syncWithoutDetaching([Permission::firstOrCreate(['title' => $permission])->id]);
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(array_fill_keys(array_keys(VehicleDocuments::checks()), 1) + ['license' => uniqid('GUIDE')]);
    }

    private function file(Vehicle $vehicle, string $type = self::DOCUMENT)
    {
        return $vehicle->media()->create(['collection_name' => 'additional_documents', 'name' => 'Guia',
            'file_name' => 'guia.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [],
            'custom_properties' => ['document_classification' => ['type' => $type]],
            'generated_conversions' => [], 'responsive_images' => []]);
    }

    public function test_guide_is_optional_and_can_be_classified_without_changing_vehicle(): void
    {
        $this->actingAs($this->actor());
        $vehicle = $this->vehicle();
        $before = $vehicle->fresh()->getAttributes();
        $this->assertArrayNotHasKey(self::DOCUMENT, VehicleDocuments::checks());
        $this->assertSame([], VehicleDocuments::pendingChecks($vehicle));
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]))->assertOk()->assertSee('Guia de transporte');
        $html = view('admin.vehicles.partials.document-summary', compact('vehicle'))->render();
        $this->assertStringContainsString('Acompanhar guia de transporte', $html);
        $this->assertStringNotContainsString('data-document="transport_guide"', $html);
        $this->assertSame(0, $vehicle->documentFollowups()->count());
        $this->assertSame(0, CalendarTask::where('vehicle_id', $vehicle->id)->count());
        $file = $this->file($vehicle, 'unknown');
        $this->patchJson(route('admin.vehicles.documents.type', [$vehicle, $file->id]), [
            'type' => self::DOCUMENT, 'revision' => VehicleDocuments::revision($file), 'reason' => 'Guia recebida',
        ])->assertOk();
        $this->assertTrue(VehicleDocuments::matches($file->fresh(), self::DOCUMENT));
        $this->assertFalse(VehicleDocuments::matches($file->fresh(), 'auction_pickup_authorization'));
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
    }

    public function test_manual_followup_appears_in_pending_list_and_closes_with_correct_file(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = $this->vehicle();
        $before = $vehicle->fresh()->getAttributes();
        $url = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $data = ['action' => 'contact', 'version' => 0, 'assigned_to_id' => $user->id,
            'contact' => 'Transportador', 'requested_on' => '2026-11-06', 'next_contact_on' => '2026-11-12', 'note' => 'Pedir guia'];
        $this->post($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $followup = $vehicle->documentFollowups()->sole();
        $task = CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->sole();
        $this->assertSame('2026-11-12', $task->getRawOriginal('due_date'));
        $review = route('admin.vehicles.documents.review', ['scope' => 'all', 'reason' => 'followups', 'license' => $vehicle->license]);
        $this->get($review)->assertOk()->assertSee('Guia de transporte')->assertSee($vehicle->license);
        $html = view('admin.vehicles.partials.document-summary', ['vehicle' => $vehicle->fresh()])->render();
        $this->assertStringContainsString('data-document="transport_guide"', $html);
        $complete = array_replace($data, ['action' => 'complete', 'version' => 1, 'confirmed' => 1]);
        $annulled = $this->file($vehicle);
        $annulled->setCustomProperty('document_annulment', ['reason' => 'Errado'])->save();
        foreach ([$this->file($vehicle, 'auction_pickup_authorization'), $this->file($vehicle, 'purchase_invoice'), $this->file($this->vehicle()), $annulled] as $wrong) {
            $this->postJson($url, $complete + ['media_id' => $wrong->id])->assertUnprocessable();
        }
        $proof = $this->file($vehicle);
        $this->post($url, $complete + ['media_id' => $proof->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('completed', $followup->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
        $this->postJson($url, $complete + ['media_id' => $proof->id])->assertStatus(409);
        $this->assertSame(1, $vehicle->documentFollowups()->count());
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
        $this->get($review)->assertOk()->assertViewHas('vehicles', fn ($rows) => $rows->isEmpty());
    }

    public function test_not_applicable_is_manual_and_preserves_existing_attachment(): void
    {
        $this->actingAs($this->actor());
        $vehicle = $this->vehicle();
        $file = $this->file($vehicle);
        $url = route('admin.vehicles.documents.applicability', [$vehicle, self::DOCUMENT]);
        $this->patchJson($url, ['status' => 'not_applicable', 'reason' => 'Conferido para esta compra',
            'revision' => VehicleDocuments::applicabilityRevision($vehicle, self::DOCUMENT)])->assertOk();
        $this->assertTrue(VehicleDocuments::notApplicable($vehicle->fresh(), self::DOCUMENT));
        $this->assertSame(1, $vehicle->media()->whereKey($file->id)->count());
        $this->patchJson($url, ['status' => 'review', 'reason' => 'Pedir novamente',
            'revision' => VehicleDocuments::applicabilityRevision($vehicle->fresh(), self::DOCUMENT)])->assertOk();
        $this->assertCount(2, $vehicle->fresh()->document_applicability[self::DOCUMENT]['history']);
    }

    public function test_permissions_and_workshop_restrictions_are_preserved(): void
    {
        $vehicle = $this->vehicle();
        $show = route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]);
        $store = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $reader = $this->actor(['vehicle_access', 'vehicle_documents_area_access']);
        $this->actingAs($reader)->get($show)->assertOk()->assertDontSee('Anexar e concluir');
        $this->post($store, [])->assertForbidden();
        $this->actingAs($this->actor(['vehicle_access', 'vehicle_edit']))->get($show)->assertForbidden();
        foreach (['Aux. oficina', 'Chefe oficina'] as $role) {
            $this->actingAs($this->actor(['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access'], $role))->get($show)->assertForbidden();
            $this->post($store, [])->assertForbidden();
        }
    }
}
