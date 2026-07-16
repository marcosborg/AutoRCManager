<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminLeadAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stand_sees_only_assigned_leads(): void
    {
        $seller = $this->userWithRole('Stand', ['lead_access', 'lead_show', 'lead_edit']);
        $otherSeller = $this->userWithRole('Stand', ['lead_access', 'lead_show', 'lead_edit']);

        $assigned = Lead::create([
            'leadgen_id' => 'lead-assigned-' . uniqid(),
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'full_name' => 'Lead Visivel',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $hidden = Lead::create([
            'leadgen_id' => 'lead-hidden-' . uniqid(),
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'full_name' => 'Lead Escondido',
            'assigned_user_id' => $otherSeller->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $this->actingAs($seller)->get(route('admin.leads.show', $assigned))->assertOk();
        $this->actingAs($seller)->get(route('admin.leads.show', $hidden))->assertForbidden();
    }

    public function test_admin_can_see_any_lead(): void
    {
        $admin = $this->userWithRole('Admin', ['lead_access', 'lead_show', 'lead_edit', 'lead_delete']);
        $seller = $this->userWithRole('Stand', ['lead_access', 'lead_show', 'lead_edit']);

        $lead = Lead::create([
            'leadgen_id' => 'lead-admin-' . uniqid(),
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'full_name' => 'Lead Admin',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $this->actingAs($admin)->get(route('admin.leads.show', $lead))->assertOk();
    }

    public function test_marketing_stand_cannot_edit_or_delete_leads_even_with_write_permissions(): void
    {
        $marketing = $this->userWithRole('Marketing Stand', [
            'lead_access',
            'lead_show',
            'lead_edit',
            'lead_delete',
        ]);

        $lead = Lead::create([
            'leadgen_id' => 'lead-marketing-read-only-'.uniqid(),
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'full_name' => 'Lead apenas leitura',
            'status' => Lead::STATUS_NEW,
        ]);

        $this->actingAs($marketing)->get(route('admin.leads.show', $lead))->assertOk();
        $this->actingAs($marketing)->get(route('admin.leads.edit', $lead))->assertForbidden();
        $this->actingAs($marketing)->put(route('admin.leads.update', $lead), [
            'status' => Lead::STATUS_CONTACTED,
        ])->assertForbidden();
        $this->actingAs($marketing)->delete(route('admin.leads.destroy', $lead))->assertForbidden();

        $this->assertSame(Lead::STATUS_NEW, $lead->fresh()->status);
        $this->assertNull($lead->fresh()->deleted_at);
    }

    public function test_lead_list_pdf_uses_filters_and_is_a_real_download(): void
    {
        $admin = $this->userWithRole('Admin', ['lead_access']);
        $seller = $this->userWithRole('Stand', ['lead_access']);

        Lead::create([
            'leadgen_id' => 'lead-pdf-'.uniqid(),
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'full_name' => 'Cliente PDF',
            'phone' => '910000001',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_QUALIFIED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.leads.export.pdf', [
            'name' => 'Cliente PDF',
            'status' => Lead::STATUS_QUALIFIED,
            'source' => 'form',
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', strtolower((string) $response->headers->get('content-disposition')));
    }

    public function test_management_api_is_read_only(): void
    {
        $admin = $this->userWithRole('Admin', ['lead_access', 'lead_show', 'lead_edit']);
        $lead = Lead::create([
            'leadgen_id' => 'lead-api-'.uniqid(),
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'full_name' => 'Cliente App',
            'phone' => '910000002',
            'status' => Lead::STATUS_NEW,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/gestao/leads?search=Cliente+App')
            ->assertOk()
            ->assertJsonPath('data.0.id', $lead->id)
            ->assertJsonStructure(['summary' => ['new', 'contacted', 'qualified', 'won', 'lost']]);

        $this->putJson("/api/v1/gestao/leads/{$lead->id}", [
            'status' => Lead::STATUS_CONTACTED,
            'assigned_user_id' => null,
        ])->assertMethodNotAllowed();

        $this->postJson("/api/v1/gestao/leads/{$lead->id}/notes", ['body' => 'Cliente contactado pela app.'])
            ->assertNotFound();

        $this->assertSame(Lead::STATUS_NEW, $lead->fresh()->status);
    }

    private function userWithRole(string $roleTitle, array $permissionTitles): User
    {
        $role = Role::firstOrCreate(['title' => $roleTitle]);

        foreach ($permissionTitles as $permissionTitle) {
            $permission = Permission::firstOrCreate(['title' => $permissionTitle]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create([
            'email' => strtolower($roleTitle) . uniqid() . '@example.com',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }
}
