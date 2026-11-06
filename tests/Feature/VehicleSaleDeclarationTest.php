<?php

namespace Tests\Feature;

use App\Models\{Client, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleSaleDeclarationTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(string $role = 'Declaration test', bool $allowed = true): User
    {
        $u = User::factory()->create();
        $r = Role::firstOrCreate(['title' => $role]);
        if ($allowed) foreach (['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access'] as $p) {
            $r->permissions()->syncWithoutDetaching(Permission::firstOrCreate(['title' => $p])->id);
        }
        $u->roles()->attach($r);
        return $u;
    }

    private function data(): array
    {
        return ['name' => 'Cliente de demonstração', 'vat' => '999999990', 'address' => 'Rua de exemplo, 1',
            'brand' => 'Marca teste', 'model' => 'Modelo teste', 'license' => 'TEST-01', 'year' => 2024,
            'total' => '20000,50', 'entry' => '0', 'deposit' => '500', 'finance' => '15000', 'debt' => '4500,50',
            'trade_value' => '0', 'guarantee' => 'yes', 'months' => 18, 'document_date' => '2026-11-06',
            'confirmed' => 1, 'company_confirmed' => 1];
    }

    public function test_prefill_preview_pdf_does_not_change_sale_or_customer(): void
    {
        $this->actingAs($this->actor());
        $client = Client::create(['name' => 'Comprador da venda', 'vat' => '999999990']);
        $v = Vehicle::create(['license' => 'DOC-01', 'client_id' => $client->id, 'pvp' => 25000]);
        $before = $v->fresh()->getAttributes();
        $clientBefore = $client->fresh()->getAttributes();
        $this->get(route('admin.vehicles.documents.sale.edit', $v))->assertOk()->assertSee('Comprador da venda');
        $this->post(route('admin.vehicles.documents.sale.prepare', $v), $this->data())->assertRedirect(route('admin.vehicles.documents.sale.preview', $v));
        $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertOk()->assertSee('20.000,50')->assertSee('Geração Determinada')->assertSee('Cliente de demonstração');
        $pdf = $this->get(route('admin.vehicles.documents.sale.pdf', $v))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame($before, $v->fresh()->getAttributes());
        $this->assertSame($clientBefore, $client->fresh()->getAttributes());
        $this->assertSame(0, $v->media()->count());
        $this->assertSame(0, $v->documentFollowups()->count());
    }

    public function test_invalid_or_unconfirmed_values_cannot_be_printed(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'DOC-02']);
        foreach ([['deposit' => -1], ['company_confirmed' => 0], ['confirmed' => 0], ['guarantee' => 'no', 'months' => 12], ['guarantee' => 'yes', 'months' => null], ['total' => '1.234,56'], ['finance' => null]] as $invalid) {
            $this->postJson(route('admin.vehicles.documents.sale.prepare', $v), array_merge($this->data(), $invalid))->assertUnprocessable();
            $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertRedirect();
        }
    }

    public function test_permissions_session_isolation_and_html_escaping(): void
    {
        $v = Vehicle::create(['license' => 'DOC-03']);
        foreach (['Aux. oficina', 'Chefe oficina', 'No declaration access'] as $role) {
            $this->actingAs($this->actor($role, $role !== 'No declaration access'));
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get(route('admin.vehicles.documents.sale.'.$action, $v))->assertForbidden();
            $this->post(route('admin.vehicles.documents.sale.prepare', $v), $this->data())->assertForbidden();
        }
        $this->actingAs($this->actor());
        $this->post(route('admin.vehicles.documents.sale.prepare', $v), array_merge($this->data(), ['name' => '<script>alert(1)</script>']))->assertRedirect();
        $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertDontSee('<script>alert(1)</script>', false);
        $other = Vehicle::create(['license' => 'DOC-04']);
        $this->get(route('admin.vehicles.documents.sale.preview', $other))->assertRedirect();
        $this->actingAs($this->actor());
        $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertRedirect();
    }
}
