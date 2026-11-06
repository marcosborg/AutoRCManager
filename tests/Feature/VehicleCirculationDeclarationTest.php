<?php
namespace Tests\Feature;

use App\Models\{Client, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleCirculationDeclarationTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Circulation ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function data(): array
    {
        return ['name' => 'Cliente de exemplo', 'brand' => 'AUDI', 'model' => 'A5',
            'license' => 'AA-00-BB', 'sold_on' => '2026-11-06', 'confirmed' => 1];
    }

    public function test_prefill_prepare_and_pdf_do_not_change_records(): void
    {
        $this->actingAs($this->actor());
        $client = Client::create(['name' => 'Comprador correto']);
        $v = Vehicle::create(['foreign_license' => 'AB-123-CD', 'client_id' => $client->id, 'color' => 'Azul']);
        $before = $v->fresh()->getAttributes();
        $clientBefore = $client->fresh()->getAttributes();
        $this->get(route('admin.vehicles.documents.circulation.edit', $v))->assertOk()->assertSee('Comprador correto')->assertSee('AB-123-CD')->assertSee('Azul');
        $this->post(route('admin.vehicles.documents.circulation.prepare', $v), $this->data())->assertSessionHasNoErrors()->assertRedirect();
        $preview = $this->get(route('admin.vehicles.documents.circulation.preview', $v))->assertOk()->assertSee('06 / 11 / 2026')->assertSee('Campos por preencher:')->assertSee('conservatória');
        $pdf = $this->get(route('admin.vehicles.documents.circulation.pdf', $v))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame($before, $v->fresh()->getAttributes());
        $this->assertSame($clientBefore, $client->fresh()->getAttributes());
        $this->assertSame(0, $v->media()->count());
        $html = view('admin.vehicles.circulation-declaration-print', ['vehicle' => $v, 'data' => $preview->viewData('data'), 'pdf' => true])->render();
        $this->assertStringNotContainsString('Campos por preencher:', $html);
        $this->assertStringNotContainsString('Alterar dados', $html);
    }

    public function test_validation_escaping_and_isolation(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'TEST']);
        foreach ([['confirmed' => 0], ['sold_on' => ''], ['sold_on' => '2026-02-30'], ['name' => ''], ['vin' => str_repeat('a', 101)]] as $bad) {
            $this->postJson(route('admin.vehicles.documents.circulation.prepare', $v), array_merge($this->data(), $bad))->assertUnprocessable();
        }
        $this->post(route('admin.vehicles.documents.circulation.prepare', $v), array_merge($this->data(), ['name' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.documents.circulation.preview', $v))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $other = Vehicle::create(['license' => 'OTHER']);
        $this->get(route('admin.vehicles.documents.circulation.preview', $other))->assertRedirect(route('admin.vehicles.documents.circulation.edit', $other));
        $this->actingAs($this->actor())->get(route('admin.vehicles.documents.circulation.pdf', $v))->assertRedirect(route('admin.vehicles.documents.circulation.edit', $v));
    }

    public function test_all_permissions_required(): void
    {
        $v = Vehicle::create(['license' => 'TEST']);
        $all = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access'];
        foreach ($all as $missing) {
            $this->actingAs($this->actor(array_values(array_diff($all, [$missing]))));
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get(route('admin.vehicles.documents.circulation.'.$action, $v))->assertForbidden();
            $this->post(route('admin.vehicles.documents.circulation.prepare', $v), $this->data())->assertForbidden();
        }
    }
}
