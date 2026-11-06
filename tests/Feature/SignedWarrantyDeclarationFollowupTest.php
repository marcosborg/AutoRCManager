<?php

namespace Tests\Feature;

use App\Models\{CalendarTask, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignedWarrantyDeclarationFollowupTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCUMENT = 'signed_warranty_declaration';

    private function actor(array $permissions = ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access'], ?string $title = null): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['title' => $title ?? uniqid('Warranty followup ')]);
        foreach ($permissions as $permission) $role->permissions()->syncWithoutDetaching([Permission::firstOrCreate(['title' => $permission])->id]);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(array_fill_keys(array_keys(VehicleDocuments::checks()), 1) + ['license' => uniqid('WARRANTY')]);
    }

    private function data(User $user): array
    {
        return ['action' => 'contact', 'version' => 0, 'assigned_to_id' => $user->id,
            'contact' => 'Comprador', 'requested_on' => '2026-11-06', 'next_contact_on' => '2026-11-12', 'note' => 'Pedir cópia assinada'];
    }

    private function file(Vehicle $vehicle, string $type = self::DOCUMENT, bool $annulled = false)
    {
        $properties = ['document_classification' => ['type' => $type]];
        if ($annulled) $properties['document_annulment'] = ['reason' => 'Substituído'];
        return $vehicle->media()->create(['collection_name' => 'additional_documents', 'name' => 'Garantia assinada',
            'file_name' => 'garantia.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [],
            'custom_properties' => $properties, 'generated_conversions' => [], 'responsive_images' => []]);
    }

    public function test_followup_is_optional_and_opening_it_creates_no_reminder(): void
    {
        $this->actingAs($this->actor());
        $vehicle = $this->vehicle();
        $this->assertArrayNotHasKey(self::DOCUMENT, VehicleDocuments::checks());
        $this->assertSame([], VehicleDocuments::pendingChecks($vehicle));
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]))->assertOk()
            ->assertSee('Conferi que a cópia está assinada e corresponde à garantia desta viatura.');
        $html = view('admin.vehicles.partials.document-summary', compact('vehicle'))->render();
        $this->assertStringContainsString('Acompanhar declaração de garantia assinada', $html);
        $this->assertStringNotContainsString('data-document="'.self::DOCUMENT.'"', $html);
        $this->assertSame(0, $vehicle->documentFollowups()->count());
        $this->assertSame(0, CalendarTask::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_completion_needs_matching_signed_copy_and_preserves_vehicle(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = $this->vehicle();
        $before = $vehicle->fresh()->getAttributes();
        $url = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $data = $this->data($user);
        $this->post($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $followup = $vehicle->documentFollowups()->sole();
        $this->assertSame('À espera de assinatura', $followup->statusLabel());
        $this->assertNull($followup->purchaseSignatureAgeAlertDays());
        $task = CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->sole();
        $this->assertSame('2026-11-12', $task->getRawOriginal('due_date'));
        $html = view('admin.vehicles.partials.document-summary', ['vehicle' => $vehicle->fresh()])->render();
        $this->assertStringContainsString('data-document="'.self::DOCUMENT.'"', $html);
        $complete = array_replace($data, ['action' => 'complete', 'version' => 1, 'confirmed' => 1]);
        $proof = $this->file($vehicle);
        $this->postJson($url, $complete + ['media_id' => $proof->id])->assertUnprocessable()->assertJsonValidationErrors('signed_confirmed');
        foreach ([$this->file($vehicle, 'signed_sale_declaration'), $this->file($vehicle, 'signed_purchase_form'), $this->file($this->vehicle()), $this->file($vehicle, self::DOCUMENT, true)] as $wrong) {
            $this->postJson($url, $complete + ['media_id' => $wrong->id, 'signed_confirmed' => 1])->assertUnprocessable();
        }
        $this->post($url, $complete + ['media_id' => $proof->id, 'signed_confirmed' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('completed', $followup->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
        $this->postJson($url, $complete + ['media_id' => $proof->id, 'signed_confirmed' => 1])->assertStatus(409);
        $this->assertSame(1, CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->count());
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
    }

    public function test_upload_requires_signature_and_saves_only_one_copy(): void
    {
        Storage::fake('public');
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = $this->vehicle();
        $url = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $upload = fn () => UploadedFile::fake()->createWithContent('garantia.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $data = array_replace($this->data($user), ['action' => 'upload_complete', 'document_file' => $upload()]);
        $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('signed_confirmed');
        $this->assertSame(0, $vehicle->media()->count());
        $data['document_file'] = $upload();
        $this->post($url, $data + ['signed_confirmed' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(self::DOCUMENT, VehicleDocuments::type($vehicle->media()->sole()));
        $this->assertSame('completed', $vehicle->documentFollowups()->sole()->status);
        $data['document_file'] = $upload();
        $this->postJson($url, $data + ['signed_confirmed' => 1])->assertStatus(409);
        $this->assertSame(1, $vehicle->media()->count());
    }

    public function test_not_applicable_preserves_files_and_blocks_followup_until_reviewed(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = $this->vehicle();
        $proof = $this->file($vehicle);
        $url = route('admin.vehicles.documents.applicability', [$vehicle, self::DOCUMENT]);
        $this->patchJson($url, ['status' => 'not_applicable', 'reason' => 'Confirmado para esta viatura',
            'revision' => VehicleDocuments::applicabilityRevision($vehicle, self::DOCUMENT)])->assertOk();
        $this->assertTrue(VehicleDocuments::notApplicable($vehicle->fresh(), self::DOCUMENT));
        $this->assertSame(1, $vehicle->media()->whereKey($proof->id)->count());
        $store = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $this->postJson($store, $this->data($user))->assertUnprocessable();
        $this->assertSame(0, $vehicle->documentFollowups()->count());
        $this->patchJson($url, ['status' => 'review', 'reason' => 'Voltar a conferir',
            'revision' => VehicleDocuments::applicabilityRevision($vehicle->fresh(), self::DOCUMENT)])->assertOk();
        $this->post($store, $this->data($user))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertCount(2, $vehicle->fresh()->document_applicability[self::DOCUMENT]['history']);
        $this->assertSame(1, $vehicle->media()->whereKey($proof->id)->count());
    }

    public function test_permissions_and_workshop_restrictions_are_preserved(): void
    {
        $vehicle = $this->vehicle();
        $show = route('admin.vehicles.documents.followup', [$vehicle, self::DOCUMENT]);
        $store = route('admin.vehicles.documents.followup.store', [$vehicle, self::DOCUMENT]);
        $reader = $this->actor(['vehicle_access', 'vehicle_documents_area_access']);
        $this->actingAs($reader)->get($show)->assertOk()->assertDontSee('Anexar e concluir');
        $this->post($store, $this->data($reader))->assertForbidden();
        $this->actingAs($this->actor(['vehicle_access', 'vehicle_edit']))->get($show)->assertForbidden();
        foreach (['Aux. oficina', 'Chefe oficina'] as $role) {
            $this->actingAs($this->actor(['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access'], $role))->get($show)->assertForbidden();
            $this->post($store, [])->assertForbidden();
        }
    }
}
