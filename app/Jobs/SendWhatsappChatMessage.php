<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Services\WhatsappCloudApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendWhatsappChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $messageId)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsappCloudApi $api): void
    {
        $message = ChatMessage::with(['conversation.lead', 'conversation.assistant', 'conversation.channel'])->find($this->messageId);
        if (! $message || $message->delivery_status !== 'pending' || $message->conversation?->channel?->slug !== 'whatsapp') {
            return;
        }

        $phone = $message->conversation->customer_phone ?: $message->conversation->lead?->phone;
        if (! $phone) {
            throw new \RuntimeException('Chat message has no destination phone.');
        }

        if (($message->metadata['type'] ?? null) === 'meta_greeting') {
            $template = config('whatsapp.templates.customer_greeting');
            $result = $api->sendTemplate($phone, $template['name'], $template['language'], [
                $message->conversation->lead?->name ?: 'Cliente',
                $message->conversation->lead?->vehicle_title ?: 'o seu pedido de informação',
                $message->conversation->assistant?->company_name ?: config('ai_assistant.company_name'),
            ]);
        } else {
            $lastCustomerMessageAt = $message->conversation->messages()
                ->where('sender', 'customer')
                ->latest('created_at')
                ->value('created_at');
            if (! $lastCustomerMessageAt || \Illuminate\Support\Carbon::parse($lastCustomerMessageAt)->lt(now()->subHours(24))) {
                $message->update([
                    'delivery_status' => 'failed',
                    'provider_status_at' => now(),
                    'metadata' => array_merge($message->metadata ?? [], [
                        'transport' => 'cloud',
                        'error' => 'customer_service_window_closed',
                    ]),
                ]);
                return;
            }
            $result = $api->sendText($phone, $message->message);
        }

        $message->update([
            'external_id' => data_get($result, 'messages.0.id'),
            'delivery_status' => 'sent',
            'sent_at' => now(),
            'provider_status_at' => now(),
            'metadata' => array_merge($message->metadata ?? [], ['transport' => 'cloud', 'cloud_response' => $result]),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        ChatMessage::whereKey($this->messageId)->where('delivery_status', 'pending')->update([
            'delivery_status' => 'failed',
            'provider_status_at' => now(),
            'metadata' => array_merge(ChatMessage::find($this->messageId)?->metadata ?? [], [
                'transport' => 'cloud',
                'error' => $exception->getMessage(),
            ]),
        ]);
    }
}
