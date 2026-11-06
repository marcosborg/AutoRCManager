<?php
namespace Tests\Feature;

use App\Models\{Client, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleResponsibilityDeclarationTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Responsibility ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function data(): array
    {
        return ['name' => 'Cliente de exemplo', 'license' => 'AA-00-BB',
            'pickup_date' => '2026-11-06', 'pickup_time' => '10:30',
            'return_date' => '2026-11-07', 'return_time' => '09:00'];
    }

    public function test_prefill_prepare_and_pdf_do_not_change_records(): void
    {
        $this->actingAs($this->actor());
        $client = Client::create(['name' => 'Cliente correto', 'vat' => '123456789']);
        $v = Vehicle::create(['foreign_license' => 'AB-123-CD', 'client_id' => $client->id, 'year' => '2020', 'month' => '03']);
        $before = $v->fresh()->getAttributes();
        $clientBefore = $client->fresh()->getAttributes();
        $this->get(route('admin.vehicles.documents.responsibility.edit', $v))->assertOk()->assertSee('Cliente correto')->assertSee('AB-123-CD')->assertSee('123456789');
        $this->post(route('admin.vehicles.documents.responsibility.prepare', $v), $this->data())->assertSessionHasNoErrors()->assertRedirect();
        $preview = $this->get(route('admin.vehicles.documents.responsibility.preview', $v))->assertOk()->assertSee('06 / 11 / 2026')->assertSee('10:30')->assertSee('Campos por preencher:')->assertSee('imposto único de circulação');
        $pdf = $this->get(route('admin.vehicles.documents.responsibility.pdf', $v))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame($before, $v->fresh()->getAttributes());
        $this->assertSame($clientBefore, $client->fresh()->getAttributes());
        $this->assertSame(0, $v->media()->count());
        $html = view('admin.vehicles.responsibility-declaration-print', ['vehicle' => $v, 'data' => $preview->viewData('data'), 'pdf' => true])->render();
        $this->assertStringNotContainsString('Campos por preencher:', $html);
        $this->assertStringNotContainsString('Alterar dados', $html);
        $this->assertStringContainsString('O Cliente', $html);
        $this->assertStringContainsString('O Stand', $html);
    }

    public function test_optional_fields_can_be_left_for_handwriting(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'TEST']);
        $this->post(route('admin.vehicles.documents.responsibility.prepare', $v), ['name' => 'Cliente', 'license' => 'TEST'])->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.documents.responsibility.preview', $v))->assertOk()->assertSee('____ / ____ / ________');
        $this->get(route('admin.vehicles.documents.responsibility.pdf', $v))->assertOk();
    }

    public function test_validation_escaping_and_isolation(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'TEST']);
        foreach ([['name' => ''], ['license' => ''], ['pickup_date' => '2026-02-30'], ['pickup_time' => '25:00'], ['vin' => str_repeat('a', 101)], ['return_date' => '2026-11-05'], ['return_date' => '2026-11-06', 'return_time' => '10:29']] as $bad) {
            $this->postJson(route('admin.vehicles.documents.responsibility.prepare', $v), array_merge($this->data(), $bad))->assertUnprocessable();
        }
        $this->post(route('admin.vehicles.documents.responsibility.prepare', $v), array_merge($this->data(), ['name' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.documents.responsibility.preview', $v))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $other = Vehicle::create(['license' => 'OTHER']);
        $this->get(route('admin.vehicles.documents.responsibility.preview', $other))->assertRedirect(route('admin.vehicles.documents.responsibility.edit', $other));
        $this->actingAs($this->actor())->get(route('admin.vehicles.documents.responsibility.pdf', $v))->assertRedirect(route('admin.vehicles.documents.responsibility.edit', $v));
    }

    public function test_all_permissions_required(): void
    {
        $v = Vehicle::create(['license' => 'TEST']);
        $all = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access'];
        foreach ($all as $missing) {
            $this->actingAs($this->actor(array_values(array_diff($all, [$missing]))));
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get(route('admin.vehicles.documents.responsibility.'.$action, $v))->assertForbidden();
            $this->post(route('admin.vehicles.documents.responsibility.prepare', $v), $this->data())->assertForbidden();
        }
    }
}
