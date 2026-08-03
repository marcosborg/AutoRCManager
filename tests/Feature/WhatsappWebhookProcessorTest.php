<?php

namespace Tests\Feature;

use App\Models\AiAssistant;
use App\Models\ChatChannel;
use App\Models\ChatConversation;
use App\Models\ChatLead;
use App\Models\ChatMessage;
use App\Services\WhatsappWebhookProcessor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WhatsappWebhookProcessorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unsupported_message_is_recorded_once_without_ai_reply(): void
    {
        $conversation = $this->conversation('351912345678');
        $payload = $this->payloadWithMessage([
            'from' => '351912345678',
            'id' => 'wamid.unsupported',
            'timestamp' => (string) now()->timestamp,
            'type' => 'image',
            'image' => ['id' => 'media-id'],
        ]);

        app(WhatsappWebhookProcessor::class)->process($payload);
        app(WhatsappWebhookProcessor::class)->process($payload);

        $messages = $conversation->messages()->where('external_id', 'wamid.unsupported')->get();
        $this->assertCount(1, $messages);
        $this->assertSame('system', $messages->first()->sender);
        $this->assertTrue($messages->first()->metadata['unsupported']);
        $this->assertSame(0, $conversation->messages()->where('sender', 'assistant')->count());
    }

    public function test_older_status_does_not_regress_delivery_state(): void
    {
        $conversation = $this->conversation('351912345679');
        $message = $conversation->messages()->create([
            'sender' => 'assistant',
            'message' => 'Resposta',
            'external_id' => 'wamid.status',
            'delivery_status' => 'read',
            'provider_status_at' => now(),
        ]);

        app(WhatsappWebhookProcessor::class)->process($this->payloadWithStatus([
            'id' => 'wamid.status',
            'status' => 'delivered',
            'timestamp' => (string) now()->subMinute()->timestamp,
            'recipient_id' => '351912345679',
        ]));

        $this->assertSame('read', $message->fresh()->delivery_status);
    }

    private function conversation(string $phone): ChatConversation
    {
        $assistant = AiAssistant::firstOrCreate(['slug' => 'whatsapp-cloud-test'], [
            'name' => 'WhatsApp Cloud Test',
            'active' => true,
        ]);
        $channel = ChatChannel::firstOrCreate(['slug' => 'whatsapp'], ['name' => 'WhatsApp', 'active' => true]);
        $lead = ChatLead::create(['channel_id' => $channel->id, 'phone' => $phone, 'source' => 'test']);

        return ChatConversation::create([
            'assistant_id' => $assistant->id,
            'lead_id' => $lead->id,
            'channel_id' => $channel->id,
            'customer_identifier' => $phone,
            'customer_phone' => $phone,
            'status' => 'active',
            'last_message_at' => now(),
        ]);
    }

    private function payloadWithMessage(array $message): array
    {
        return ['entry' => [['changes' => [[
            'field' => 'messages',
            'value' => [
                'metadata' => ['phone_number_id' => '123'],
                'contacts' => [['wa_id' => $message['from'], 'profile' => ['name' => 'Cliente']]],
                'messages' => [$message],
            ],
        ]]]]];
    }

    private function payloadWithStatus(array $status): array
    {
        return ['entry' => [['changes' => [[
            'field' => 'messages',
            'value' => ['statuses' => [$status]],
        ]]]]];
    }
}
