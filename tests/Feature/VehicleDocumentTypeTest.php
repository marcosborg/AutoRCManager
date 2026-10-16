<?php

namespace Tests\Feature;

use App\Models\{AuditLog, GeneralState, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleDocumentTypeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.document-type-tests' => ['driver' => 'local']]);
        Storage::fake('document-type-tests');
    }

    public function test_classification_correction_and_reset_preserve_file_vehicle_and_other_metadata(): void
    {
        $vehicle = $this->vehicle();
        $file = $this->file($vehicle);
        $file->setCustomProperty('existing', ['keep' => true])->save();
        $before = $file->fresh()->getAttributes();
        $vehicleBefore = $vehicle->fresh()->getAttributes();
        $bytes = file_get_contents($file->getPath());
        $user = $this->user();
        foreach (['dua', 'inspection', 'unknown'] as $type) {
            $response = $this->actingAs($user)->patchJson($this->url($vehicle, $file), $this->payload($file, $type))->assertOk()->assertJsonPath('type', $type);
            $this->assertSame($type, VehicleDocuments::type($file->fresh()));
            $this->assertStringContainsString($type === 'unknown' ? 'Ficheiros disponíveis por identificar' : 'Ficheiro identificado: 1', $response->json('summary_html'));
        }
        $after = $file->fresh()->getAttributes();
        unset($before['custom_properties'], $before['updated_at'], $after['custom_properties'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->assertSame(['keep' => true], $file->fresh()->getCustomProperty('existing'));
        $this->assertSame($vehicleBefore, $vehicle->fresh()->getAttributes());
        $this->assertSame($bytes, file_get_contents($file->getPath()));
        $this->assertSame(3, AuditLog::where('description', 'vehicle_document:classified')->where('subject_id', $file->id)->where('user_id', $user->id)->count());
        $this->actingAs($user)->get(route('admin.vehicles.edit', $vehicle))->assertOk()->assertSee('Identificar os ficheiros guardados')->assertSee('Ficheiros disponíveis por identificar');
    }

    public function test_only_an_explicit_combined_type_counts_as_both_dua_and_inspection(): void
    {
        $vehicle = $this->vehicle();
        $file = $this->file($vehicle);
        $this->actingAs($this->user())->patchJson($this->url($vehicle, $file), $this->payload($file, 'dua'))->assertOk();
        $this->assertSame(1, VehicleDocuments::count($vehicle->fresh('media'), 'dua'));
        $this->assertSame(0, VehicleDocuments::count($vehicle->fresh('media'), 'inspection'));
        $this->patchJson($this->url($vehicle, $file), $this->payload($file, 'dua_inspection'))->assertOk();
        $this->assertSame(1, VehicleDocuments::count($vehicle->fresh('media'), 'inspection'));
        $this->assertFalse((bool) $vehicle->fresh()->vehicle_ownership_title);
    }

    public function test_dav_can_be_corrected_without_moving_the_file_or_retaining_false_dav_evidence(): void
    {
        $vehicle = $this->vehicle();
        $file = $this->file($vehicle, 'dav');
        $this->assertSame('dav', VehicleDocuments::type($file));
        $this->actingAs($this->user())->patchJson($this->url($vehicle, $file), $this->payload($file, 'purchase_invoice'))->assertOk()->assertJsonPath('type', 'purchase_invoice');
        $this->assertSame(0, VehicleDocuments::count($vehicle->fresh('media'), 'dav'));
        $this->assertSame(1, VehicleDocuments::count($vehicle->fresh('media'), 'purchase_invoice'));
        $this->assertSame('dav', $file->fresh()->collection_name);
    }

    public function test_repeated_submission_is_idempotent_and_stale_corrections_are_rejected(): void
    {
        $vehicle = $this->vehicle();
        $file = $this->file($vehicle);
        $payload = $this->payload($file, 'dua');
        $this->actingAs($this->user())->patchJson($this->url($vehicle, $file), $payload)->assertOk();
        $this->patchJson($this->url($vehicle, $file), $payload)->assertOk();
        $this->patchJson($this->url($vehicle, $file), array_replace($payload, ['type' => 'inspection']))->assertStatus(409);
        $this->assertSame('dua', VehicleDocuments::type($file->fresh()));
        $this->assertSame(1, AuditLog::where('description', 'vehicle_document:classified')->where('subject_id', $file->id)->count());
    }

    public function test_rejects_other_vehicles_other_collections_and_invalid_types(): void
    {
        $vehicle = $this->vehicle();
        $other = $this->file($this->vehicle());
        $photo = $this->file($vehicle, 'photos');
        $file = $this->file($vehicle, 'additional_documents');
        $this->actingAs($this->user());
        foreach ([$other, $photo] as $invalid) {
            $this->patchJson($this->url($vehicle, $invalid), $this->payload($invalid, 'dua'))->assertNotFound();
        }
        $this->patchJson($this->url($vehicle, $file), $this->payload($file, 'invented'))->assertUnprocessable();
        $this->assertSame('unknown', VehicleDocuments::type($file->fresh()));
        $this->assertNull($photo->fresh()->getCustomProperty('document_classification'));
    }

    public function test_web_and_api_enforce_both_existing_permissions(): void
    {
        $vehicle = $this->vehicle();
        $file = $this->file($vehicle);
        $api = '/api/v1/backoffice/vehicles/'.$vehicle->id.'/documents/'.$file->id.'/type';
        $this->patchJson($api, $this->payload($file, 'dua'))->assertUnauthorized();
        foreach ([[], ['vehicle_edit'], ['vehicle_documents_area_access']] as $permissions) {
            $user = $this->user($permissions);
            $this->actingAs($user)->patchJson($this->url($vehicle, $file), $this->payload($file, 'dua'))->assertForbidden();
            \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
            $this->patchJson($api, $this->payload($file, 'dua'))->assertForbidden();
        }
        $this->assertSame('unknown', VehicleDocuments::type($file->fresh()));
    }

    public function test_authorized_integration_can_classify_with_the_same_validation(): void
    {
        $vehicle = $this->vehicle();
        $file = $this->file($vehicle, 'additional_documents');
        $user = $this->user();
        $user->roles()->attach(Role::firstOrCreate(['title' => 'Admin']));
        $token = $user->createToken('integration:document-type-test')->plainTextToken;
        $api = '/api/v1/backoffice/vehicles/'.$vehicle->id.'/documents/'.$file->id.'/type';
        $this->patchJson($api, $this->payload($file, 'purchase_invoice'), ['Authorization' => 'Bearer '.$token])
            ->assertOk()->assertJsonPath('type', 'purchase_invoice');
        $this->assertSame('purchase_invoice', VehicleDocuments::type($file->fresh()));
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(['license' => uniqid('TYP-'), 'general_state_id' => GeneralState::firstOrCreate(['name' => 'DOCUMENT TYPE TEST'])->id]);
    }

    private function file(Vehicle $vehicle, string $collection = 'documents')
    {
        return $vehicle->addMedia(UploadedFile::fake()->create('dua-inspecao.pdf', 1, 'application/pdf'))->toMediaCollection($collection, 'document-type-tests');
    }

    private function payload($file, string $type): array
    {
        return ['type' => $type, 'revision' => VehicleDocuments::revision($file->fresh())];
    }

    private function url($vehicle, $file): string
    {
        return route('admin.vehicles.documents.type', [$vehicle, $file->id]);
    }

    private function user(array $permissions = ['vehicle_edit', 'vehicle_documents_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Document type ')]);
        foreach ($permissions as $title) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $title])->id);
        }
        $user->roles()->attach($role);
        return $user;
    }
}
