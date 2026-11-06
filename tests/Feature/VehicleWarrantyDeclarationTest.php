<?php

namespace Tests\Feature;

use App\Models\{Client, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleWarrantyDeclarationTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Warranty ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function data(): array
    {
        return ['name' => 'Cliente de exemplo', 'vat' => '999999990', 'brand' => 'AUDI', 'model' => 'A5',
            'license' => 'AA-00-BB', 'confirmed' => 1];
    }

    public function test_prefill_preview_and_pdf_preserve_records_and_leave_unagreed_terms_blank(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $client = Client::create(['name' => 'Comprador correto', 'vat' => '999999990']);
        $v = Vehicle::create(['foreign_license' => 'AB-123-CD', 'client_id' => $client->id, 'kilometers' => 12345]);
        $before = $v->fresh()->getAttributes();
        $clientBefore = $client->fresh()->getAttributes();
        $this->get(route('admin.vehicles.documents.warranty.edit', $v))->assertOk()->assertSee('Comprador correto')->assertSee('AB-123-CD')->assertSee('12345');
        $this->post(route('admin.vehicles.documents.warranty.prepare', $v), $this->data())->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('admin.vehicles.documents.warranty.preview', $v))->assertOk()->assertSee('DECLARAÇÃO DE GARANTIA AUTOMÓVEL')->assertSee('________ / ________ / ________');
        $pdf = $this->get(route('admin.vehicles.documents.warranty.pdf', $v))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame($before, $v->fresh()->getAttributes());
        $this->assertSame($clientBefore, $client->fresh()->getAttributes());
        $this->assertSame(0, $v->media()->count());
        $this->assertSame(0, $v->documentFollowups()->count());
        $this->assertNull(session('warranty_declaration_v1.'.$user->id.'.'.$v->id)['starts_on']);
    }

    public function test_explicit_dates_kilometers_and_escaped_text_are_printed(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'WARRANTY']);
        $this->post(route('admin.vehicles.documents.warranty.prepare', $v), array_merge($this->data(), [
            'name' => '<script>alert(1)</script>', 'identity' => '12345678', 'vin' => 'WAUZZZ12345678901',
            'kilometers' => 0, 'covered_kilometers' => 20000, 'starts_on' => '2026-11-06', 'ends_on' => '2028-05-06',
        ]))->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.documents.warranty.preview', $v))->assertOk()
            ->assertSee('06 / 11 / 2026')->assertSee('06 / 05 / 2028')->assertSee('20.000')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false)->assertSee('WAUZZZ12345678901');
    }

    public function test_validation_and_session_isolation(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $v = Vehicle::create(['license' => 'WARRANTY']);
        foreach ([['confirmed' => 0], ['name' => ''], ['starts_on' => '2026-11-06'],
            ['starts_on' => '2026-11-06', 'ends_on' => '2026-11-05'], ['covered_kilometers' => -1], ['kilometers' => 'abc']] as $invalid) {
            $this->postJson(route('admin.vehicles.documents.warranty.prepare', $v), array_merge($this->data(), $invalid))->assertUnprocessable();
        }
        $this->post(route('admin.vehicles.documents.warranty.prepare', $v), $this->data())->assertRedirect();
        $other = Vehicle::create(['license' => 'OTHER']);
        $this->get(route('admin.vehicles.documents.warranty.preview', $other))->assertRedirect(route('admin.vehicles.documents.warranty.edit', $other));
        $this->actingAs($this->actor())->get(route('admin.vehicles.documents.warranty.pdf', $v))->assertRedirect(route('admin.vehicles.documents.warranty.edit', $v));
    }

    public function test_each_permission_is_required_on_all_endpoints(): void
    {
        $v = Vehicle::create(['license' => 'WARRANTY']);
        $all = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access'];
        foreach ($all as $missing) {
            $this->actingAs($this->actor(array_values(array_diff($all, [$missing]))));
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get(route('admin.vehicles.documents.warranty.'.$action, $v))->assertForbidden();
            $this->post(route('admin.vehicles.documents.warranty.prepare', $v), $this->data())->assertForbidden();
        }
    }
}
