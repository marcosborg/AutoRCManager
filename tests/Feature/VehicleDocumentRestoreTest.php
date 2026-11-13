<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleDocumentRestoreTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.document-restore-tests' => ['driver' => 'local']]);
        Storage::fake('document-restore-tests');
    }

    private function actor(array $permissions = ['vehicle_edit', 'vehicle_documents_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Document restore ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function fixture(): array
    {
        $vehicle = Vehicle::create(['license' => uniqid('REST-')]);
        $file = $vehicle->addMedia(UploadedFile::fake()->create('document.pdf', 1, 'application/pdf'))->toMediaCollection('documents', 'document-restore-tests');
        $file->setCustomProperty('document_classification', ['type' => 'dua', 'user_id' => 1]);
        $file->setCustomProperty('document_annulment', ['reason' => 'Conferir validade', 'user_name' => 'Autor anterior']);
        $file->setCustomProperty('keep', ['original' => true])->save();
        return [$vehicle, $file, route('admin.vehicles.documents.restore', [$vehicle, $file->id])];
    }

    public function test_restore_preserves_file_classification_vehicle_and_audits_once_on_retry(): void
    {
        [$vehicle, $file, $url] = $this->fixture();
        $vehicleBefore = $vehicle->fresh()->getAttributes();
        $bytes = file_get_contents($file->getPath());
        $classification = $file->getCustomProperty('document_classification');
        $payload = ['reason' => '  Validade confirmada  ', 'revision' => VehicleDocuments::revision($file)];
        $this->actingAs($this->actor())->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertOk();
        $file->refresh();
        $this->assertFalse(VehicleDocuments::annulled($file));
        $this->assertSame($classification, $file->getCustomProperty('document_classification'));
        $this->assertSame(['original' => true], $file->getCustomProperty('keep'));
        $this->assertSame($bytes, file_get_contents($file->getPath()));
        $this->assertSame($vehicleBefore, $vehicle->fresh()->getAttributes());
        $this->assertSame(1, VehicleDocuments::count($vehicle->fresh('media'), 'dua'));
        $logs = AuditLog::where('description', 'vehicle_document:restored')->where('subject_id', $file->id)->get();
        $this->assertCount(1, $logs);
        $this->assertSame('Validade confirmada', $logs->first()->properties['reason']);
        $this->assertSame('Conferir validade', $logs->first()->properties['previous_annulment']['reason']);
        $this->get(route('admin.vehicles.documents.history', [$vehicle, $file->id]))->assertOk()->assertSee('Validade confirmada')->assertDontSee('Repor este anexo');
    }

    public function test_invalid_or_stale_restore_leaves_annulment_unchanged(): void
    {
        [$vehicle, $file, $url] = $this->fixture();
        $this->actingAs($this->actor());
        $before = $file->fresh()->getAttributes();
        $this->postJson($url, ['reason' => '   ', 'revision' => VehicleDocuments::revision($file)])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson($url, ['reason' => 'Confirmado', 'revision' => str_repeat('0', 64)])->assertStatus(409);
        $this->assertSame($before, $file->fresh()->getAttributes());
    }

    public function test_api_restore_requires_authentication_and_records_history(): void
    {
        [$vehicle, $file] = $this->fixture();
        $base = '/api/v1/backoffice/vehicles/'.$vehicle->id.'/documents/'.$file->id;
        $data = ['reason' => 'Validade conferida', 'revision' => VehicleDocuments::revision($file)];
        $this->postJson($base.'/restore', $data)->assertUnauthorized();
        $user = $this->actor();
        $user->roles()->attach(Role::firstOrCreate(['title' => 'Admin']));
        $token = $user->createToken('integration:restore-test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->postJson($base.'/restore', $data, $headers)->assertOk();
        $this->getJson($base.'/history', $headers)->assertOk()->assertJsonPath('annulled', false)
            ->assertJsonPath('history.0.properties.reason', 'Validade conferida')->assertJsonMissingPath('history.0.host');
    }

    public function test_previous_restore_cannot_be_replayed_after_another_annulment(): void
    {
        [$vehicle, $file, $url] = $this->fixture();
        $this->actingAs($this->actor());
        $old = ['reason' => 'Conferido', 'revision' => VehicleDocuments::revision($file)];
        $this->postJson($url, $old)->assertOk();
        $this->postJson(route('admin.vehicles.documents.annul', [$vehicle, $file->id]), [
            'reason' => 'Nova dúvida', 'revision' => VehicleDocuments::revision($file->fresh()),
        ])->assertOk();
        $this->postJson($url, $old)->assertStatus(409);
        $this->assertTrue(VehicleDocuments::annulled($file->fresh()));
        $this->postJson($url, ['reason' => 'Conferido novamente', 'revision' => VehicleDocuments::revision($file->fresh())])->assertOk();
        $this->assertFalse(VehicleDocuments::annulled($file->fresh()));
        $this->assertSame(2, AuditLog::where('description', 'vehicle_document:restored')->where('subject_id', $file->id)->count());
    }

    public function test_restore_requires_both_permissions_and_media_belonging_to_vehicle(): void
    {
        [$vehicle, $file, $url] = $this->fixture();
        $data = ['reason' => 'Confirmado', 'revision' => VehicleDocuments::revision($file)];
        foreach ([['vehicle_edit'], ['vehicle_documents_area_access']] as $permissions) {
            $this->actingAs($this->actor($permissions))->postJson($url, $data)->assertForbidden();
        }
        $this->actingAs($this->actor());
        $other = Vehicle::create(['license' => uniqid('OTHER-')]);
        $this->postJson(route('admin.vehicles.documents.restore', [$other, $file->id]), $data)->assertNotFound();
        $this->assertTrue(VehicleDocuments::annulled($file->fresh()));
        $this->get(route('admin.vehicles.documents.history', [$vehicle, $file->id]))->assertOk()->assertSee('Repor este anexo');
    }
}
