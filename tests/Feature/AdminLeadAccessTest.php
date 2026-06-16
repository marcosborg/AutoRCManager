<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
            'full_name' => 'Lead Visivel',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $hidden = Lead::create([
            'leadgen_id' => 'lead-hidden-' . uniqid(),
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
            'full_name' => 'Lead Admin',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $this->actingAs($admin)->get(route('admin.leads.show', $lead))->assertOk();
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
