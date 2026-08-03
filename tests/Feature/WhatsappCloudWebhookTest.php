<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsappWebhook;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsappCloudWebhookTest extends TestCase
{
    public function test_verification_accepts_the_configured_token(): void
    {
        config(['whatsapp.verify_token' => 'verify-secret']);

        $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=verify-secret&hub.challenge=12345')
            ->assertOk()
            ->assertSeeText('12345');
    }

    public function test_verification_rejects_an_invalid_token(): void
    {
        config(['whatsapp.verify_token' => 'verify-secret']);

        $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')
            ->assertForbidden();
    }

    public function test_signed_payload_is_queued(): void
    {
        Queue::fake();
        config(['whatsapp.app_secret' => 'app-secret']);
        $json = json_encode(['object' => 'whatsapp_business_account', 'entry' => []], JSON_THROW_ON_ERROR);
        $signature = 'sha256=' . hash_hmac('sha256', $json, 'app-secret');

        $this->call('POST', '/api/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $json)->assertOk();

        Queue::assertPushed(ProcessWhatsappWebhook::class, fn ($job) => $job->payload['object'] === 'whatsapp_business_account');
    }

    public function test_invalid_signature_is_rejected_without_queuing(): void
    {
        Queue::fake();
        config(['whatsapp.app_secret' => 'app-secret']);

        $this->call('POST', '/api/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], '{"entry":[]}')->assertForbidden();

        Queue::assertNothingPushed();
    }
}
