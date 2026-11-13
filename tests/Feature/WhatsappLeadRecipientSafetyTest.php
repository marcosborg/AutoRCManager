<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappLeadNotification;
use App\Models\{Lead, LeadWhatsappNotification, User};
use App\Services\WhatsappCloudApi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WhatsappLeadRecipientSafetyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['whatsapp.transport' => 'email', 'whatsapp.lead_notifications_enabled' => true,
            'whatsapp.templates.seller_lead' => ['name' => 'test', 'language' => 'pt_PT']]);
    }

    public function test_reassigned_job_is_skipped_without_api_email_or_changes_to_current_delivery(): void
    {
        [$lead, $notification] = $this->notification();
        $current = User::factory()->create();
        $lead->update(['assigned_user_id' => $current->id, 'seller_notification_status' => 'sent',
            'seller_notified_user_id' => $current->id, 'seller_notified_at' => now()]);
        $before = $lead->fresh()->getAttributes();
        $api = \Mockery::mock(WhatsappCloudApi::class);
        $api->shouldNotReceive('sendTemplate');
        $job = new SendWhatsappLeadNotification($notification->id);
        $job->handle($api);
        $firstSkip = $notification->fresh()->getAttributes();
        $job->handle($api);
        $job->failed(new \RuntimeException('Old queue failure'));
        $this->assertSame($firstSkip, $notification->fresh()->getAttributes());
        $this->assertSame($before, $lead->fresh()->getAttributes());
        $this->assertSame('failed', $notification->fresh()->status);
        $this->assertSame('skipped', $notification->fresh()->metadata['delivery_status']);
        $this->assertSame('recipient_changed', $notification->fresh()->metadata['failure_kind']);
        $this->assertSame('preserved', $notification->fresh()->metadata['audit_marker']);
        $this->assertEquals(0, $notification->fresh()->attempts);
        $this->assertNull($notification->fresh()->sent_at);
        Mail::assertNothingSent();
    }

    public function test_failed_callback_does_not_email_a_previous_seller(): void
    {
        [$lead, $notification] = $this->notification();
        $lead->update(['assigned_user_id' => null]);
        $before = $lead->fresh()->getAttributes();
        (new SendWhatsappLeadNotification($notification->id))->failed(new \RuntimeException('Queue failure'));
        $this->assertSame($before, $lead->fresh()->getAttributes());
        $this->assertSame('skipped', $notification->fresh()->metadata['delivery_status']);
        Mail::assertNothingSent();
    }

    public function test_current_assignee_can_receive_without_an_authenticated_browser_session_once(): void
    {
        [$lead, $notification] = $this->notification();
        $this->assertGuest();
        $api = \Mockery::mock(WhatsappCloudApi::class);
        $api->shouldReceive('sendTemplate')->once()->andReturn(['messages' => [['id' => 'wamid.safety.test']]]);
        $job = new SendWhatsappLeadNotification($notification->id);
        $job->handle($api);
        $job->handle($api);
        $this->assertSame('sent', $notification->fresh()->status);
        $this->assertEquals(1, $notification->fresh()->attempts);
        $this->assertSame('sent', $lead->fresh()->seller_notification_status);
        Mail::assertNothingSent();
    }

    private function notification(): array
    {
        $seller = User::factory()->create();
        $lead = Lead::create(['leadgen_id' => uniqid('recipient-safety-'), 'page_id' => 'test-page',
            'form_id' => 'test-form', 'assigned_user_id' => $seller->id, 'seller_notification_status' => 'pending']);
        $notification = LeadWhatsappNotification::create(['lead_id' => $lead->id, 'user_id' => $seller->id,
            'phone' => '351912000001', 'message' => 'Teste', 'status' => LeadWhatsappNotification::STATUS_PENDING,
            'metadata' => ['audit_marker' => 'preserved']]);

        return [$lead, $notification];
    }
}
