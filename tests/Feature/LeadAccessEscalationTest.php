<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadAccessToken;
use App\Models\LeadSalesRotation;
use App\Models\LeadWhatsappNotification;
use App\Models\Role;
use App\Models\User;
use App\Services\LeadAccessEscalationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeadAccessEscalationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['title' => 'Stand']);
        DB::table('role_user')->where('role_id', $role->id)->delete();
    }

    public function test_seller_can_open_link_within_first_hour_and_keeps_week_access(): void
    {
        $seller = $this->standUser('seller-one@example.com', '911000001');
        $lead = $this->leadFor($seller);
        $plainToken = Str::random(72);

        $accessToken = LeadAccessToken::create([
            'lead_id' => $lead->id,
            'user_id' => $seller->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
            'first_open_deadline_at' => now()->addHour(),
        ]);

        $this->get(route('lead-access.show', $plainToken))->assertOk();

        $accessToken->refresh();
        $this->assertNotNull($accessToken->last_used_at);
        $this->assertNull($accessToken->revoked_at);
        $this->assertSame($seller->id, $lead->fresh()->assigned_user_id);
    }

    public function test_unopened_link_after_one_hour_is_revoked_and_lead_moves_to_another_seller(): void
    {
        $firstSeller = $this->standUser('seller-two@example.com', '911000002');
        $nextSeller = $this->standUser('seller-three@example.com', '911000003');
        $lead = $this->leadFor($firstSeller);
        $plainToken = Str::random(72);

        $accessToken = LeadAccessToken::create([
            'lead_id' => $lead->id,
            'user_id' => $firstSeller->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
            'first_open_deadline_at' => now()->subMinute(),
        ]);

        $this->get(route('lead-access.show', $plainToken))
            ->assertStatus(410)
            ->assertSee('Lead transitada');

        $accessToken->refresh();
        $lead->refresh();

        $this->assertNotNull($accessToken->revoked_at);
        $this->assertSame(LeadAccessEscalationService::REVOKED_NO_OPEN_TIMEOUT, $accessToken->revoked_reason);
        $this->assertSame($nextSeller->id, $lead->assigned_user_id);

        $notification = LeadWhatsappNotification::where('lead_id', $lead->id)->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame($nextSeller->id, $notification->user_id);
        $this->assertSame('351911000003', $notification->phone);
        $this->assertSame(LeadWhatsappNotification::STATUS_PENDING, $notification->status);
        $this->assertNotNull($notification->access_token?->first_open_deadline_at);
    }

    public function test_lead_rotation_restarts_after_all_eligible_sellers_timeout(): void
    {
        $firstSeller = $this->standUser('seller-four@example.com', '911000004');
        $secondSeller = $this->standUser('seller-five@example.com', '911000005');
        $thirdSeller = $this->standUser('seller-six@example.com', '911000006');
        $this->standUser('seller-no-phone@example.com');

        LeadSalesRotation::query()->create(['last_user_id' => $thirdSeller->id]);

        $lead = $this->leadFor($thirdSeller);
        foreach ([$firstSeller, $secondSeller] as $seller) {
            LeadAccessToken::create([
                'lead_id' => $lead->id,
                'user_id' => $seller->id,
                'token_hash' => hash('sha256', Str::random(72)),
                'expires_at' => now()->addDays(7),
                'first_open_deadline_at' => now()->subHour(),
                'revoked_at' => now()->subMinutes(30),
                'revoked_reason' => LeadAccessEscalationService::REVOKED_NO_OPEN_TIMEOUT,
            ]);
        }

        $plainToken = Str::random(72);
        $currentToken = LeadAccessToken::create([
            'lead_id' => $lead->id,
            'user_id' => $thirdSeller->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
            'first_open_deadline_at' => now()->subMinute(),
        ]);

        $this->get(route('lead-access.show', $plainToken))
            ->assertStatus(410)
            ->assertSee('Lead transitada');

        $currentToken->refresh();
        $lead->refresh();

        $this->assertSame(LeadAccessEscalationService::REVOKED_NO_OPEN_TIMEOUT, $currentToken->revoked_reason);
        $this->assertSame($firstSeller->id, $lead->assigned_user_id);

        $notification = LeadWhatsappNotification::where('lead_id', $lead->id)->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame($firstSeller->id, $notification->user_id);
        $this->assertSame('351911000004', $notification->phone);
        $this->assertSame(LeadWhatsappNotification::STATUS_PENDING, $notification->status);
    }

    private function standUser(string $email, ?string $mobilePhone = null): User
    {
        $role = Role::firstOrCreate(['title' => 'Stand']);
        $user = User::factory()->create([
            'email' => Str::before($email, '@') . '-' . Str::random(10) . '@example.com',
            'mobile_phone' => $mobilePhone,
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function leadFor(User $seller): Lead
    {
        return Lead::create([
            'leadgen_id' => 'lead-access-escalation-' . Str::random(16),
            'page_id' => 'test',
            'form_id' => 'test',
            'full_name' => 'Cliente Teste',
            'phone' => '351912345678',
            'vehicle_interest' => 'SUV',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_NEW,
        ]);
    }
}
