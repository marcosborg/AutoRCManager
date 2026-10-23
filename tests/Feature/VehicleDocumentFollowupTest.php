<?php

namespace Tests\Feature;

use App\Models\CalendarTask;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleDocumentFollowupTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $permissions = ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access']): User
    {
        $u = User::factory()->create();
        $r = Role::create(['title' => uniqid('Followup ')]);
        foreach ($permissions as $p) {
            $r->permissions()->attach(Permission::firstOrCreate(['title' => $p])->id);
        }
        $u->roles()->attach($r);

        return $u;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(array_merge(array_fill_keys(array_keys(VehicleDocuments::checks()), 1), ['license' => uniqid('FOLLOW'), 'model' => 'Followup test']));
    }

    private function data(User $u, array $extra = []): array
    {
        return array_merge(['action' => 'contact', 'version' => 0, 'assigned_to_id' => $u->id, 'contact' => 'Fornecedor teste', 'requested_on' => '2026-10-20', 'next_contact_on' => '2026-10-26', 'note' => 'Pedido de cópia digital'], $extra);
    }

    private function url(Vehicle $v): string
    {
        return route('admin.vehicles.documents.followup.store', [$v, 'vehicle_ownership_title']);
    }

    private function file(Vehicle $v, string $type = 'dua')
    {
        return $v->media()->create(['collection_name' => 'documents', 'name' => 'Document test', 'file_name' => 'document.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [], 'custom_properties' => ['document_classification' => ['type' => $type]], 'generated_conversions' => [], 'responsive_images' => []]);
    }

    public function test_followup_history_reminder_and_stale_write_without_duplicates(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        $v = $this->vehicle();
        $this->get(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title']))->assertOk()->assertSee('Guardar acompanhamento');
        $this->post($this->url($v), $this->data($u))->assertRedirect();
        $f = $v->documentFollowups()->first();
        $this->assertSame('open', $f->status);
        $this->assertSame($u->id, $f->history[0]['user_id']);
        $this->post($this->url($v), $this->data($u))->assertStatus(409);
        $this->post($this->url($v), $this->data($u, ['version' => 1, 'action' => 'reschedule', 'next_contact_on' => '2026-10-28', 'note' => 'Novo prazo']))->assertRedirect();
        $this->assertCount(2, $f->fresh()->history);
        $this->assertSame(1, CalendarTask::where('dedupe_key', 'document-followup:'.$f->id)->count());
        $this->assertSame('2026-10-28', CalendarTask::where('dedupe_key', 'document-followup:'.$f->id)->first()->getRawOriginal('due_date'));
        $this->get(route('admin.vehicles.documents.review', ['scope' => 'all', 'license' => $v->license]))->assertOk()->assertSee('data-vehicle-id="'.$v->id.'"', false);
        $this->get(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title']))->assertOk()->assertSee('Novo prazo');
        $this->assertSame(1, (int) $v->fresh()->vehicle_ownership_title);
    }

    public function test_digital_copy_same_vehicle_type_and_human_confirmation_required(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        $v = $this->vehicle();
        $base = $this->data($u, ['action' => 'complete', 'confirmed' => 1]);
        $this->postJson($this->url($v), $base)->assertUnprocessable();
        foreach (['unknown', 'other', 'purchase_invoice'] as $type) {
            $this->postJson($this->url($v), $base + ['media_id' => $this->file($v, $type)->id])->assertUnprocessable();
        }
        $this->postJson($this->url($v), $base + ['media_id' => $this->file($this->vehicle())->id])->assertUnprocessable();
        $file = $this->file($v, 'dua_inspection');
        $this->postJson($this->url($v), array_merge($base, ['media_id' => $file->id, 'confirmed' => 0]))->assertUnprocessable();
        $this->post($this->url($v), $base + ['media_id' => $file->id])->assertRedirect();
        $f = $v->documentFollowups()->first();
        $this->assertSame('completed', $f->status);
        $this->assertNotNull(CalendarTask::where('dedupe_key', 'document-followup:'.$f->id)->first()->completed_at);
        $file->setCustomProperty('document_classification', ['type' => 'other'])->save();
        $this->assertSame('review', $f->fresh()->status);
        $this->assertCount(2, $f->fresh()->history);
    }

    public function test_dispense_restore_and_deleted_proof_preserve_history(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        $v = $this->vehicle();
        $this->post($this->url($v), $this->data($u))->assertRedirect();
        $url = route('admin.vehicles.documents.applicability', [$v, 'vehicle_ownership_title']);
        $this->patchJson($url, ['status' => 'not_applicable', 'reason' => 'Decisão teste', 'revision' => VehicleDocuments::applicabilityRevision($v, 'vehicle_ownership_title')])->assertOk();
        $f = $v->documentFollowups()->first();
        $this->assertSame('dispensed', $f->status);
        $this->postJson($this->url($v), $this->data($u, ['version' => 2]))->assertUnprocessable();
        $this->patchJson($url, ['status' => 'review', 'reason' => 'Reavaliar', 'revision' => VehicleDocuments::applicabilityRevision($v->fresh(), 'vehicle_ownership_title')])->assertOk();
        $this->assertSame('review', $f->fresh()->status);
        $file = $this->file($v);
        $this->post($this->url($v), $this->data($u, ['version' => 3, 'action' => 'complete', 'media_id' => $file->id, 'confirmed' => 1]))->assertRedirect();
        $file->delete();
        $this->assertSame('review', $f->fresh()->status);
        $this->assertCount(5, $f->fresh()->history);
    }

    public function test_permissions_and_calendar_cannot_bypass_copy_requirement(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        $v = $this->vehicle();
        $this->post($this->url($v), $this->data($u))->assertRedirect();
        $f = $v->documentFollowups()->first();
        $task = CalendarTask::where('dedupe_key', 'document-followup:'.$f->id)->first();
        $this->post(route('admin.systemCalendar.tasks.complete', $task))->assertRedirect(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title'], false));
        $this->assertNull($task->fresh()->completed_at);
        foreach ([[], ['vehicle_edit'], ['vehicle_documents_area_access']] as $permissions) {
            $other = $this->user($permissions);
            $this->actingAs($other);
            $this->get(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title']))->assertForbidden();
            $this->postJson($this->url($v), $this->data($other, ['version' => 1]))->assertForbidden();
        }
        $u->roles()->detach();
        $this->actingAs($u->fresh());
        $this->get(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title']))->assertForbidden();
        $this->assertFalse(CalendarTask::whereKey($task->id)->visibleTo($u->fresh())->exists());
    }

    public function test_api_authentication_read_only_access_and_invalid_assignment(): void
    {
        $v = $this->vehicle();
        $url = '/api/v1/backoffice/vehicles/'.$v->id.'/documents/vehicle_ownership_title/followup';
        $this->getJson($url)->assertUnauthorized();
        $reader = $this->user(['vehicle_access', 'vehicle_documents_area_access']);
        $this->actingAs($reader)->get(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title']))->assertOk()->assertDontSee('Guardar acompanhamento');
        $this->postJson($this->url($v), $this->data($reader))->assertForbidden();
        $editor = $this->user();
        $this->actingAs($editor)->postJson($this->url($v), $this->data($reader))->assertUnprocessable();
    }

    public function test_authorized_api_can_read_and_create_followup(): void
    {
        $v = $this->vehicle();
        $url = '/api/v1/backoffice/vehicles/'.$v->id.'/documents/vehicle_ownership_title/followup';
        $editor = $this->user();
        $editor->roles()->attach(Role::firstOrCreate(['title' => 'Admin']));
        $token = $editor->createToken('integration:followup-test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson($url)->assertOk()->assertJsonPath('vehicle_id', $v->id)->assertJsonMissingPath('vehicle.purchase_price');
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson($url, $this->data($editor))->assertOk();
        $this->assertSame(1, $v->documentFollowups()->count());
    }

    private function uploadPdf()
    {
        return \Illuminate\Http\UploadedFile::fake()->createWithContent('DUA.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    public function test_direct_upload_identifies_copy_without_completing_and_can_then_be_confirmed(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $u = $this->user();
        $this->actingAs($u);
        $v = $this->vehicle();
        $before = $v->fresh()->getAttributes();
        $data = $this->data($u, ['action' => 'upload', 'note' => null, 'document_file' => $this->uploadPdf()]);
        $this->post($this->url($v), $data)->assertRedirect()->assertSessionHas('uploaded_media_id');
        $file = $v->media()->first();
        $f = $v->documentFollowups()->first();
        $this->assertSame('dua', VehicleDocuments::type($file));
        $this->assertSame('documents', $file->collection_name);
        $this->assertSame('public', $file->disk);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($file->getPathRelativeToRoot());
        $this->assertSame('open', $f->status);
        $this->assertNull($f->media_id);
        $this->assertSame('upload', $f->history[0]['action']);
        $this->assertEquals($before, $v->fresh()->getAttributes());
        $this->get(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title']))->assertOk()->assertSee($file->file_name);
        $this->postJson($this->url($v), $this->data($u, ['action' => 'upload', 'document_file' => $this->uploadPdf()]))->assertStatus(409);
        $this->assertSame(1, $v->media()->count());
        $this->postJson($this->url($v), $this->data($u, ['version' => 1, 'action' => 'complete', 'media_id' => $file->id]))->assertUnprocessable();
        $this->post($this->url($v), $this->data($u, ['version' => 1, 'action' => 'complete', 'media_id' => $file->id, 'confirmed' => 1]))->assertRedirect();
        $this->assertSame('completed', $f->fresh()->status);
    }

    public function test_upload_rejects_missing_unsafe_oversized_files_and_unauthorized_user(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $u = $this->user();
        $v = $this->vehicle();
        $this->actingAs($u);
        $this->postJson($this->url($v), $this->data($u, ['action' => 'upload']))->assertUnprocessable();
        foreach ([\Illuminate\Http\UploadedFile::fake()->createWithContent('bad.php', '<?php echo "unsafe";'), \Illuminate\Http\UploadedFile::fake()->create('large.pdf', 2049, 'application/pdf')] as $file) {
            $this->postJson($this->url($v), $this->data($u, ['action' => 'upload', 'document_file' => $file]))->assertUnprocessable();
        }
        $this->postJson($this->url($v), $this->data($u, ['action' => 'contact', 'document_file' => $this->uploadPdf()]))->assertUnprocessable();
        $reader = $this->user(['vehicle_access', 'vehicle_documents_area_access']);
        $this->actingAs($reader)->postJson($this->url($v), $this->data($u, ['action' => 'upload', 'document_file' => $this->uploadPdf()]))->assertForbidden();
        $this->assertSame(0, $v->media()->count());
        $this->assertSame(0, $v->documentFollowups()->count());
    }

    public function test_upload_preserves_existing_proof_and_completion(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $u = $this->user();
        $v = $this->vehicle();
        $this->actingAs($u);
        $original = $this->file($v);
        $this->post($this->url($v), $this->data($u, ['action' => 'complete', 'media_id' => $original->id, 'confirmed' => 1]))->assertRedirect();
        $this->post($this->url($v), $this->data($u, ['version' => 1, 'action' => 'upload', 'document_file' => $this->uploadPdf()]))->assertRedirect();
        $f = $v->documentFollowups()->first();
        $this->assertSame('completed', $f->status);
        $this->assertSame($original->id, (int) $f->media_id);
        $this->assertSame(2,$v->media()->count());
        $this->assertCount(2,$f->history);
    }
    public function test_attach_and_complete_saves_proof_closes_reminder_and_rejects_repetition(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $u = $this->user();
        $v = $this->vehicle();
        $this->actingAs($u);
        $before = $v->fresh()->getAttributes();
        $original = $this->file($v, 'unknown');
        $this->post($this->url($v), $this->data($u))->assertRedirect();
        $data = $this->data($u, ['version' => 1, 'action' => 'upload_complete', 'note' => null]);
        $this->postJson($this->url($v), $data)->assertUnprocessable();
        $this->postJson($this->url($v), $data + ['document_file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('bad.php', '<?php echo 1;')])->assertUnprocessable();
        $this->assertSame('open', $v->documentFollowups()->first()->status);
        $this->post($this->url($v), $data + ['document_file' => $this->uploadPdf()])->assertRedirect();
        $f = $v->documentFollowups()->first();
        $file = $v->media()->findOrFail($f->media_id);
        $this->assertSame('completed', $f->status);
        $this->assertSame('dua', VehicleDocuments::type($file));
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($file->getPathRelativeToRoot());
        $this->assertNotNull(CalendarTask::where('dedupe_key', 'document-followup:'.$f->id)->first()->completed_at);
        $this->assertCount(2, $f->history);
        $this->assertSame('complete', $f->history[1]['action']);
        $this->assertSame($u->id, $f->history[1]['user_id']);
        $this->assertEquals($before, $v->fresh()->getAttributes());
        $this->assertNotNull($original->fresh());
        $this->get(route('admin.vehicles.documents.followup', [$v, 'vehicle_ownership_title']))->assertOk()->assertSee('Concluído')->assertSee($file->file_name);
        $this->postJson($this->url($v), $data + ['document_file' => $this->uploadPdf()])->assertStatus(409);
        $this->assertSame(2, $v->media()->count());
        $reader = $this->user(['vehicle_access', 'vehicle_documents_area_access']);
        $this->actingAs($reader)->postJson($this->url($v), $data + ['document_file' => $this->uploadPdf()])->assertForbidden();
    }

}
