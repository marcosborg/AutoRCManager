<?php
namespace Tests\Feature;

use App\Models\{Permission, Role, User, Vehicle, Suplier};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehiclePurchaseFormTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $role = 'Purchase form', array $permissions = ['vehicle_edit', 'vehicle_documents_area_access', 'suplier_create', 'aquisition_of_the_vehicle']): User
    {
        $user = User::factory()->create();
        $r = Role::firstOrCreate(['title' => $role]);
        foreach ($permissions as $p) $r->permissions()->syncWithoutDetaching(Permission::firstOrCreate(['title' => $p])->id);
        $user->roles()->attach($r);
        return $user;
    }

    private function data(Suplier $buyer): array
    {
        return ['seller_id' => $buyer->id, 'buyer_id' => 'auto_rafael', 'buyer_name' => 'Ignored tampered buyer', 'buyer_nif' => '999999990', 'buyer_address' => 'Rua da Empresa, 1', 'seller_name' => 'Particular Exemplo', 'seller_nif' => '999999991', 'seller_address' => 'Rua do Vendedor, 2', 'amount' => '12500,50', 'purchase_date' => '2026-10-23', 'place' => 'Ovar', 'confirmed' => 1];
    }

    public function test_prepare_preview_pdf_preserves_purchase_and_keeps_parties_separate(): void
    {
        $this->actingAs($this->user());
        $seller = Suplier::create(['name' => 'Vendedor original', 'nif' => '999999991']);
        $buyer = Suplier::create(['name' => 'Compradora Exemplo', 'nif' => '999999990']);
        $vehicle = Vehicle::create(['license' => 'TEST-07', 'model' => 'Teste', 'suplier_id' => $seller->id, 'purchase_price' => 100, 'amount_paid' => 0]);
        $before = $vehicle->fresh()->getAttributes();
        $url = route('admin.vehicles.documents.purchase.prepare', $vehicle);
        $this->get(route('admin.vehicles.documents.purchase.edit', $vehicle))->assertOk()->assertSee('Vendedor original')->assertSee('Criar novo vendedor')->assertDontSee('Criar nova empresa compradora');
        $this->post($url, $this->data($buyer))->assertRedirect(route('admin.vehicles.documents.purchase.preview', $vehicle));
        $this->get(route('admin.vehicles.documents.purchase.preview', $vehicle))->assertOk()->assertSee('Particular Exemplo')->assertSee('Auto Rafael')->assertDontSee('Ignored tampered buyer')->assertSee('12.500,50')->assertSee('Não indicado');
        $pdf = $this->get(route('admin.vehicles.documents.purchase.pdf', $vehicle))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->post($url, $this->data($buyer))->assertRedirect();
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
        $this->assertSame('Vendedor original', $seller->fresh()->name);
        $this->assertSame(0, $vehicle->media()->count());
        $this->get(route('admin.vehicles.documents.purchase.edit', $vehicle))->assertSee('Particular Exemplo')->assertSee('12500.50');
        $this->get(route('admin.purchase-form.sellers'))->assertOk()->assertJsonFragment(['id' => $buyer->id, 'name' => $buyer->name]);
    }

    public function test_incomplete_unconfirmed_and_deleted_buyers_are_rejected(): void
    {
        $this->actingAs($this->user());
        $v = Vehicle::create(['license' => 'TEST-EMPTY']);
        $b = Suplier::create(['name' => 'Buyer']);
        $url = route('admin.vehicles.documents.purchase.prepare', $v);
        $this->post($url, [])->assertSessionHasErrors(['buyer_id', 'seller_name', 'amount', 'confirmed']);
        $this->post($url, array_merge($this->data($b), ['confirmed' => 0, 'buyer_address' => ' ', 'amount' => '-1']))->assertSessionHasErrors(['confirmed', 'buyer_address', 'amount']);
        $b->delete();
        $this->post($url, $this->data($b))->assertSessionHasErrors('seller_id');
        $this->get(route('admin.vehicles.documents.purchase.pdf', $v))->assertRedirect();
    }

    public function test_workshop_and_unauthorized_users_cannot_read_or_generate_documents(): void
    {
        $v = Vehicle::create(['license' => 'TEST-PRIVATE']);
        foreach (['Aux. oficina', 'Chefe oficina', 'No purchase permission'] as $role) {
            $this->actingAs($this->user($role, $role === 'No purchase permission' ? [] : ['vehicle_edit', 'vehicle_documents_area_access']));
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get(route('admin.vehicles.documents.purchase.'.$action, $v))->assertForbidden();
            $this->post(route('admin.vehicles.documents.purchase.prepare', $v), [])->assertForbidden();
            $this->get(route('admin.purchase-form.buyers'))->assertForbidden();
            $this->get(route('admin.purchase-form.sellers'))->assertForbidden();
        }
    }

    public function test_preview_is_private_to_session_and_vehicle_and_escapes_text(): void
    {
        $this->actingAs($this->user());
        $a = Vehicle::create(['license' => 'TEST-A']);
        $b = Vehicle::create(['license' => 'TEST-B']);
        $buyer = Suplier::create(['name' => 'Buyer']);
        $this->post(route('admin.vehicles.documents.purchase.prepare', $a), array_merge($this->data($buyer), ['seller_name' => '<script>alert(1)</script>']))->assertRedirect();
        $this->get(route('admin.vehicles.documents.purchase.preview', $a))->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('admin.vehicles.documents.purchase.preview', $b))->assertRedirect();
        $this->flushSession();
        $this->get(route('admin.vehicles.documents.purchase.preview', $a))->assertRedirect();
    }
    public function test_only_group_companies_can_buy_and_old_drafts_cannot_be_printed(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $v = Vehicle::create(['license' => 'GROUP-07']);
        $seller = Suplier::create(['name' => 'Particular cadastrado']);
        $url = route('admin.vehicles.documents.purchase.prepare', $v);
        $this->withSession(['purchase_form.'.$user->id.'.'.$v->id => $this->data($seller)])
            ->get(route('admin.vehicles.documents.purchase.preview', $v))->assertRedirect();
        foreach (['outside_company', (string) $seller->id] as $invalid) {
            $this->post($url, array_merge($this->data($seller), ['buyer_id' => $invalid]))->assertSessionHasErrors('buyer_id');
        }
        foreach (Vehicle::DESTINATION_COMPANIES as $key => $name) {
            $this->post($url, array_merge($this->data($seller), ['buyer_id' => $key]))->assertRedirect();
            $this->get(route('admin.vehicles.documents.purchase.preview', $v))->assertOk()->assertSee($name)->assertDontSee('Ignored tampered buyer');
        }
        $this->get(route('admin.purchase-form.buyers'))->assertOk()->assertJsonCount(3);
    }

    public function test_saved_form_opens_one_followup_and_preserves_completed_history(): void
    {
        $user = $this->user('Purchase tracking', ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access', 'aquisition_of_the_vehicle']);
        $this->actingAs($user);
        $seller = Suplier::create(['name' => 'Particular teste']);
        $vehicle = Vehicle::create(['license' => 'SIGNED-08', 'model' => 'Teste']);
        $before = $vehicle->fresh()->getAttributes();
        $url = route('admin.vehicles.documents.purchase.prepare', $vehicle);
        $data = $this->data($seller) + ['intent' => 'save', 'followup_version' => 0, 'assigned_to_id' => $user->id, 'next_contact_on' => now()->addDays(3)->toDateString()];
        $this->post($url, $data)->assertRedirect(route('admin.vehicles.documents.followup', [$vehicle, 'signed_purchase_form']));
        $f = $vehicle->documentFollowups()->sole();
        $this->assertSame('open', $f->status);
        $this->assertSame('À espera de assinatura', $f->statusLabel());
        $this->assertSame($user->id, $f->assigned_to_id);
        $this->post($url, $data)->assertRedirect();
        $this->assertSame(1, $vehicle->documentFollowups()->count());
        $this->assertSame(1, \App\Models\CalendarTask::where('dedupe_key', 'document-followup:'.$f->id)->count());
        $this->post($url, array_merge($data, ['amount' => '99']))->assertStatus(409);
        $this->flushSession();
        $this->get(route('admin.vehicles.documents.purchase.preview', $vehicle))->assertOk()->assertSee('12.500,50');
        $file = $vehicle->media()->create(['collection_name' => 'additional_documents', 'name' => 'Assinado', 'file_name' => 'assinado.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [], 'custom_properties' => ['document_classification' => ['type' => 'signed_purchase_form']], 'generated_conversions' => [], 'responsive_images' => []]);
        $complete = ['action' => 'complete', 'version' => $f->version, 'assigned_to_id' => $user->id, 'contact' => 'Vendedor', 'requested_on' => now()->toDateString(), 'next_contact_on' => now()->addDays(3)->toDateString(), 'note' => 'Assinatura conferida', 'confirmed' => 1, 'media_id' => $file->id];
        $followupUrl = route('admin.vehicles.documents.followup.store', [$vehicle, 'signed_purchase_form']);
        $this->postJson($followupUrl, $complete)->assertUnprocessable()->assertJsonValidationErrors('signed_confirmed');
        $this->post($followupUrl, $complete + ['signed_confirmed' => 1])->assertRedirect();
        $history = $f->fresh()->history;
        $this->post($url, $data)->assertRedirect();
        $this->assertSame('completed', $f->fresh()->status);
        $this->assertSame($history, $f->fresh()->history);
        $this->post($url, array_merge($data, ['followup_version' => $f->fresh()->version, 'amount' => '99']))->assertStatus(409);
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
        $this->get(route('admin.vehicles.documents.followup', [$vehicle, 'signed_purchase_form']))->assertOk()->assertSee('Ver e imprimir impresso guardado')->assertSee('Abrir anexo:');
        $file->setCustomProperty('document_annulment', ['reason' => 'Engano'])->save();
        $this->assertSame('review', $f->fresh()->status);
        $complete['version'] = $f->fresh()->version;
        $this->postJson($followupUrl, $complete + ['signed_confirmed' => 1])->assertUnprocessable();
    }

    public function test_saved_form_requires_authorized_responsible_and_keeps_preview_separate(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        $v = Vehicle::create(['license' => 'NO-SAVE']);
        $seller = Suplier::create(['name' => 'Vendedor']);
        $url = route('admin.vehicles.documents.purchase.prepare', $v);
        $this->post($url, $this->data($seller))->assertRedirect();
        $this->assertSame(0, $v->documentFollowups()->count());
        $this->postJson($url, $this->data($seller) + ['intent' => 'save'])->assertUnprocessable();
        $unauthorized = User::factory()->create();
        $this->postJson($url, $this->data($seller) + ['intent' => 'save', 'followup_version' => 0, 'assigned_to_id' => $unauthorized->id, 'next_contact_on' => now()->addDay()->toDateString()])->assertUnprocessable();
        $this->assertSame(0, $v->documentFollowups()->count());
    }

    public function test_signed_upload_requires_signature_confirmation_and_rejects_foreign_proof(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $u = $this->user('Signed uploads', ['vehicle_access', 'vehicle_edit', 'vehicle_documents_area_access', 'aquisition_of_the_vehicle']);
        $this->actingAs($u);
        $v = Vehicle::create(['license' => 'UPLOAD-08']);
        $other = Vehicle::create(['license' => 'OTHER-08']);
        $foreign = $other->media()->create(['collection_name' => 'additional_documents', 'name' => 'Assinado', 'file_name' => 'foreign.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [], 'custom_properties' => ['document_classification' => ['type' => 'signed_purchase_form']], 'generated_conversions' => [], 'responsive_images' => []]);
        $url = route('admin.vehicles.documents.followup.store', [$v, 'signed_purchase_form']);
        $base = ['action' => 'complete', 'version' => 0, 'assigned_to_id' => $u->id, 'contact' => 'Vendedor', 'requested_on' => now()->toDateString(), 'next_contact_on' => now()->addDay()->toDateString(), 'note' => 'Conferido', 'confirmed' => 1, 'signed_confirmed' => 1, 'media_id' => $foreign->id];
        $this->postJson($url, $base)->assertUnprocessable();
        unset($base['media_id'], $base['signed_confirmed']);
        $base['action'] = 'upload_complete';
        $base['document_file'] = \Illuminate\Http\UploadedFile::fake()->image('compra-assinada.jpg');
        $this->postJson($url, $base)->assertUnprocessable()->assertJsonValidationErrors('signed_confirmed');
        $this->assertSame(0, $v->media()->count());
        $this->post($url, $base + ['signed_confirmed' => 1])->assertRedirect();
        $f = $v->documentFollowups()->sole();
        $this->assertSame('completed', $f->status);
        $this->assertSame('signed_purchase_form', \App\Support\VehicleDocuments::type($v->media()->sole()));
        $base['document_file'] = \Illuminate\Http\UploadedFile::fake()->image('compra-assinada-reenvio.jpg');
        $this->post($url, $base + ['signed_confirmed' => 1])->assertStatus(409);
        $this->assertSame(1, $v->media()->count());
    }

}
