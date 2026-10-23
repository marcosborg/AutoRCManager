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
        return ['buyer_id' => $buyer->id, 'buyer_name' => $buyer->name, 'buyer_nif' => '999999990', 'buyer_address' => 'Rua da Empresa, 1', 'seller_name' => 'Particular Exemplo', 'seller_nif' => '999999991', 'seller_address' => 'Rua do Vendedor, 2', 'amount' => '12500,50', 'purchase_date' => '2026-10-23', 'place' => 'Ovar', 'confirmed' => 1];
    }

    public function test_prepare_preview_pdf_preserves_purchase_and_keeps_parties_separate(): void
    {
        $this->actingAs($this->user());
        $seller = Suplier::create(['name' => 'Vendedor original', 'nif' => '999999991']);
        $buyer = Suplier::create(['name' => 'Compradora Exemplo', 'nif' => '999999990']);
        $vehicle = Vehicle::create(['license' => 'TEST-07', 'model' => 'Teste', 'suplier_id' => $seller->id, 'purchase_price' => 100, 'amount_paid' => 0]);
        $before = $vehicle->fresh()->getAttributes();
        $url = route('admin.vehicles.documents.purchase.prepare', $vehicle);
        $this->get(route('admin.vehicles.documents.purchase.edit', $vehicle))->assertOk()->assertSee('Vendedor original')->assertSee('Criar nova empresa compradora');
        $this->post($url, $this->data($buyer))->assertRedirect(route('admin.vehicles.documents.purchase.preview', $vehicle));
        $this->get(route('admin.vehicles.documents.purchase.preview', $vehicle))->assertOk()->assertSee('Particular Exemplo')->assertSee('Compradora Exemplo')->assertSee('12.500,50')->assertSee('Não indicado');
        $pdf = $this->get(route('admin.vehicles.documents.purchase.pdf', $vehicle))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->post($url, $this->data($buyer))->assertRedirect();
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
        $this->assertSame('Vendedor original', $seller->fresh()->name);
        $this->assertSame(0, $vehicle->media()->count());
        $this->get(route('admin.vehicles.documents.purchase.edit', $vehicle))->assertSee('Particular Exemplo')->assertSee('12500.50');
        $this->get(route('admin.purchase-form.buyers'))->assertOk()->assertJsonFragment(['id' => $buyer->id, 'name' => $buyer->name]);
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
        $this->post($url, $this->data($b))->assertSessionHasErrors('buyer_id');
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
}
