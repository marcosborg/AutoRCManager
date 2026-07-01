<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadWhatsappNotification;
use App\Models\Role;
use App\Models\User;
use App\Services\LeadWhatsappNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SystemMaintenanceLeadNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        LeadWhatsappNotification::query()->delete();
        Lead::query()->delete();
    }

    public function test_admin_can_resend_lead_notifications_and_node_endpoint_returns_pending_data(): void
    {
        config(['ai_assistant.node_api_token' => 'node-token']);

        $admin = $this->userWithRole('Admin');
        $seller = $this->seller();
        $lead = $this->lead($seller, '2026-06-30 19:12:00');

        $response = $this->actingAs($admin)->post(route('admin.system-maintenance.resend-lead-notifications'), [
            'since' => '2026-06-30 19:11:00',
        ]);

        $response->assertRedirect(route('admin.system-maintenance.index'));
        $response->assertSessionHas('lead_resend_result');

        $result = session('lead_resend_result');
        $this->assertSame(1, $result['queued']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame([], $result['errors']);

        $this->getJson('/api/whatsapp/lead-notifications', [
            'Authorization' => 'Bearer node-token',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'lead_id' => $lead->id,
                'user_id' => $seller->id,
                'phone' => '351912000001',
            ]);
    }

    public function test_resend_skips_existing_pending_notification_for_same_lead_and_seller(): void
    {
        $seller = $this->seller();
        $lead = $this->lead($seller, '2026-06-30 19:12:00');

        LeadWhatsappNotification::create([
            'lead_id' => $lead->id,
            'user_id' => $seller->id,
            'phone' => '351912000001',
            'message' => 'Mensagem ja pendente',
            'status' => LeadWhatsappNotification::STATUS_PENDING,
        ]);

        $result = app(LeadWhatsappNotificationService::class)
            ->resendNotifications('2026-06-30 19:11:00');

        $this->assertSame(0, $result['queued']);
        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, LeadWhatsappNotification::where('lead_id', $lead->id)->where('user_id', $seller->id)->get());
    }

    public function test_non_admin_cannot_resend_lead_notifications(): void
    {
        $user = $this->userWithRole('Stand');

        $this->actingAs($user)->post(route('admin.system-maintenance.resend-lead-notifications'), [
            'since' => '2026-06-30 19:11:00',
        ])->assertForbidden();
    }

    private function userWithRole(string $roleTitle): User
    {
        $role = Role::firstOrCreate(['title' => $roleTitle]);
        $user = User::factory()->create([
            'email' => strtolower($roleTitle) . uniqid() . '@example.com',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function seller(): User
    {
        $seller = $this->userWithRole('Stand');
        $seller->update(['mobile_phone' => '912000001']);

        return $seller;
    }

    private function lead(User $seller, string $createdAt): Lead
    {
        $lead = Lead::create([
            'leadgen_id' => 'lead-resend-' . uniqid(),
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'full_name' => 'Cliente Reenfileirar',
            'phone' => '912345678',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $lead->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $lead;
    }
}
