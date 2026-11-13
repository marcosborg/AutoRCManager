<?php

namespace Tests\Feature;

use App\Mail\LeadWhatsappFallbackMail;
use App\Models\{Lead, LeadWhatsappNotification, User};
use App\Services\LeadWhatsappNotificationFailureHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadWhatsappFallbackIsolationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_failure_only_changes_its_own_lead_and_email_is_not_repeated(): void
    {
        Mail::fake();
        config(['whatsapp.transport' => 'email']);
        $seller = User::factory()->create();
        $create = fn () => Lead::create(['leadgen_id' => uniqid('fallback-'), 'page_id' => 'fallback-test-page', 'form_id' => 'fallback-test-form', 'assigned_user_id' => $seller->id,
            'seller_notification_status' => 'sent', 'seller_notified_user_id' => $seller->id, 'seller_notified_at' => now()]);
        $target = $create();
        $unrelated = $create();
        $before = $unrelated->fresh()->getAttributes();
        $notification = LeadWhatsappNotification::create(['lead_id' => $target->id, 'user_id' => $seller->id,
            'phone' => '351912000001', 'message' => 'Teste', 'status' => LeadWhatsappNotification::STATUS_SENT]);
        $handler = app(LeadWhatsappNotificationFailureHandler::class);
        $handler->handle($notification, 'Falha de entrega');
        $handler->handle($notification->fresh(), 'Falha de entrega repetida');
        $this->assertSame('failed', $target->fresh()->seller_notification_status);
        $this->assertNull($target->fresh()->seller_notified_at);
        $this->assertSame($before, $unrelated->fresh()->getAttributes());
        Mail::assertSent(LeadWhatsappFallbackMail::class, 1);
        Mail::assertSent(LeadWhatsappFallbackMail::class, fn ($mail) => $mail->hasTo($seller->email) && $mail->notification->lead_id === $target->id);
    }

    public function test_failure_for_previous_seller_does_not_clear_current_seller_delivery(): void
    {
        Mail::fake();
        config(['whatsapp.transport' => 'email']);
        $oldSeller = User::factory()->create();
        $currentSeller = User::factory()->create();
        $lead = Lead::create(['leadgen_id' => uniqid('reassigned-'), 'page_id' => 'fallback-test-page', 'form_id' => 'fallback-test-form', 'assigned_user_id' => $currentSeller->id,
            'seller_notification_status' => 'sent', 'seller_notified_user_id' => $currentSeller->id, 'seller_notified_at' => now()]);
        $before = $lead->fresh()->getAttributes();
        $notification = LeadWhatsappNotification::create(['lead_id' => $lead->id, 'user_id' => $oldSeller->id,
            'phone' => '351912000001', 'message' => 'Teste', 'status' => LeadWhatsappNotification::STATUS_SENT]);
        app(LeadWhatsappNotificationFailureHandler::class)->handle($notification, 'Falha antiga');
        $this->assertSame($before, $lead->fresh()->getAttributes());
        $this->assertSame(LeadWhatsappNotification::STATUS_FAILED, $notification->fresh()->status);
    }

    public function test_send_and_delivery_webhook_only_update_their_own_lead(): void
    {
        Mail::fake();
        config(['whatsapp.transport' => 'email', 'whatsapp.lead_notifications_enabled' => true,
            'whatsapp.templates.seller_lead' => ['name' => 'test', 'language' => 'pt_PT']]);
        $seller = User::factory()->create();
        $create = fn () => Lead::create(['leadgen_id' => uniqid('send-'), 'page_id' => 'test-page', 'form_id' => 'test-form',
            'assigned_user_id' => $seller->id, 'seller_notification_status' => 'pending']);
        $target = $create(); $unrelated = $create();
        $before = $unrelated->fresh()->getAttributes();
        $notification = LeadWhatsappNotification::create(['lead_id' => $target->id, 'user_id' => $seller->id,
            'phone' => '351912000001', 'message' => 'Teste', 'status' => LeadWhatsappNotification::STATUS_PENDING]);
        $api = \Mockery::mock(\App\Services\WhatsappCloudApi::class);
        $externalId = uniqid('wamid.isolation.');
        $api->shouldReceive('sendTemplate')->once()->andReturn(['messages' => [['id' => $externalId]]]);
        (new \App\Jobs\SendWhatsappLeadNotification($notification->id))->handle($api);
        $this->assertSame('sent', $target->fresh()->seller_notification_status);
        $this->assertSame($before, $unrelated->fresh()->getAttributes());
        $target->update(['seller_notification_status' => 'pending']);
        app(\App\Services\WhatsappWebhookProcessor::class)->process(['entry' => [['changes' => [[
            'field' => 'messages', 'value' => ['statuses' => [['id' => $externalId, 'status' => 'delivered', 'timestamp' => (string) now()->addSecond()->timestamp]]],
        ]]]]]);
        $this->assertSame('sent', $target->fresh()->seller_notification_status);
        $this->assertSame('delivered', $notification->fresh()->metadata['delivery_status']);
        $this->assertSame($before, $unrelated->fresh()->getAttributes());
        Mail::assertNothingSent();
    }

    public function test_delayed_failed_webhook_preserves_event_time_and_accepts_later_delivery(): void
    {
        Mail::fake();
        config(['whatsapp.transport' => 'email']);
        $seller = User::factory()->create();
        $lead = Lead::create(['leadgen_id' => uniqid('ordering-'), 'page_id' => 'test-page', 'form_id' => 'test-form',
            'assigned_user_id' => $seller->id, 'seller_notification_status' => 'sent']);
        $notification = LeadWhatsappNotification::create(['lead_id' => $lead->id, 'user_id' => $seller->id,
            'phone' => '351912000001', 'message' => 'Teste', 'status' => LeadWhatsappNotification::STATUS_SENT,
            'external_id' => uniqid('wamid.ordering.'), 'provider_status_at' => now()->subMinutes(10), 'metadata' => ['delivery_status' => 'sent']]);
        $failedAt = now()->subMinutes(5)->startOfSecond();
        $deliveredAt = now()->subMinutes(4)->startOfSecond();
        $process = function (string $status, $at) use ($notification) {
            app(\App\Services\WhatsappWebhookProcessor::class)->process(['entry' => [['changes' => [[
                'field' => 'messages', 'value' => ['statuses' => [['id' => $notification->external_id, 'status' => $status, 'timestamp' => (string) $at->timestamp]]],
            ]]]]]);
        };
        $process('failed', $failedAt);
        $this->assertSame($failedAt->timestamp, $notification->fresh()->provider_status_at->timestamp);
        $this->assertSame('failed', $notification->fresh()->metadata['delivery_status']);
        $process('delivered', $deliveredAt);
        $this->assertSame('delivered', $notification->fresh()->metadata['delivery_status']);
        $this->assertSame($deliveredAt->timestamp, $notification->fresh()->provider_status_at->timestamp);
        $this->assertNull($notification->fresh()->failed_at);
        $this->assertSame('sent', $lead->fresh()->seller_notification_status);
        $process('failed', $failedAt);
        $this->assertSame('delivered', $notification->fresh()->metadata['delivery_status']);
        Mail::assertSent(LeadWhatsappFallbackMail::class, 1);
    }
}
