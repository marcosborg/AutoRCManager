<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\LeadWhatsappNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookProcessor
{
    public function process(array $payload): void
    {
        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    $this->processMessage($message, $value);
                }
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->processStatus($status);
                }
            }
        }
    }

    private function processMessage(array $message, array $value): void
    {
        $externalId = (string) ($message['id'] ?? '');
        if ($externalId === '') {
            return;
        }

        Cache::lock('whatsapp-message:' . hash('sha256', $externalId), 30)->block(5, function () use ($message, $value, $externalId) {
            if (ChatMessage::withTrashed()->where('external_id', $externalId)->exists()) {
                return;
            }

            $phone = (string) ($message['from'] ?? '');
            $name = collect($value['contacts'] ?? [])->firstWhere('wa_id', $phone)['profile']['name'] ?? null;
            $type = (string) ($message['type'] ?? 'unknown');
            $text = $type === 'text' ? trim((string) data_get($message, 'text.body')) : '';
            $metadata = [
                'source' => 'whatsapp_cloud_webhook',
                'type' => $type,
                'timestamp' => $message['timestamp'] ?? null,
                'phone_number_id' => data_get($value, 'metadata.phone_number_id'),
                'raw_message' => $message,
            ];

            if ($this->isBusinessAppOutgoing($message)) {
                if ($text !== '') {
                    app(AiLeadAssistantService::class)->handleHumanOutgoingMessage([
                        'channel' => 'whatsapp',
                        'phone' => (string) ($message['to'] ?? $phone),
                        'message' => $text,
                        'message_id' => $externalId,
                        'metadata' => $metadata + ['source' => 'whatsapp_business_app'],
                    ]);
                }
                return;
            }

            if ($type !== 'text' || $text === '') {
                $this->recordUnsupported($phone, $externalId, $type, $metadata);
                return;
            }

            app(AiLeadAssistantService::class)->handleIncomingMessage([
                'channel' => 'whatsapp',
                'phone' => $phone,
                'name' => $name,
                'message' => $text,
                'message_id' => $externalId,
                'metadata' => $metadata,
            ]);

        });
    }

    private function processStatus(array $status): void
    {
        $externalId = (string) ($status['id'] ?? '');
        $state = (string) ($status['status'] ?? '');
        if ($externalId === '' || ! in_array($state, ['sent', 'delivered', 'read', 'failed'], true)) {
            return;
        }

        $occurredAt = isset($status['timestamp'])
            ? Carbon::createFromTimestampUTC((int) $status['timestamp'])
            : now();

        $message = ChatMessage::where('external_id', $externalId)->first();
        if ($message && $this->shouldAcceptStatus($message->delivery_status, $message->provider_status_at, $state, $occurredAt)) {
            $message->update([
                'delivery_status' => $state,
                'provider_status_at' => $occurredAt,
                'metadata' => array_merge($message->metadata ?? [], ['last_cloud_status' => $status]),
            ]);
        }

        $notification = LeadWhatsappNotification::where('external_id', $externalId)->first();
        $notificationCurrentState = (string) ($notification?->metadata['delivery_status'] ?? 'pending');
        if ($notification && $this->shouldAcceptStatus($notificationCurrentState, $notification->provider_status_at, $state, $occurredAt)) {
            if ($state === 'failed') {
                app(LeadWhatsappNotificationFailureHandler::class)->handle(
                    $notification,
                    (string) data_get($status, 'errors.0.message', 'WhatsApp Cloud API reported a failed delivery.'),
                    ['delivery_status' => 'failed', 'last_cloud_status' => $status]
                );
                return;
            }
            $notification->update([
                'status' => LeadWhatsappNotification::STATUS_SENT,
                'failed_at' => null,
                'provider_status_at' => $occurredAt,
                'metadata' => array_merge($notification->metadata ?? [], [
                    'delivery_status' => $state,
                    'last_cloud_status' => $status,
                ]),
            ]);

            $notification->lead?->where('assigned_user_id', $notification->user_id)->update([
                'seller_notification_status' => 'sent',
                'seller_notified_user_id' => $notification->user_id,
                'seller_notified_at' => $occurredAt,
            ]);
        }
    }

    private function recordUnsupported(string $phone, string $externalId, string $type, array $metadata): void
    {
        $conversation = ChatConversation::query()
            ->where('customer_phone', preg_replace('/\D+/', '', $phone))
            ->where('status', '!=', 'closed')
            ->latest('last_message_at')
            ->first();

        if ($conversation) {
            $conversation->messages()->create([
                'sender' => 'system',
                'message' => "Mensagem WhatsApp não suportada: {$type}",
                'external_id' => $externalId,
                'delivery_status' => 'delivered',
                'metadata' => $metadata + ['unsupported' => true],
                'sent_at' => now(),
            ]);
            $conversation->update(['last_message_at' => now()]);
        } else {
            Log::info('Unsupported WhatsApp message received without an existing conversation.', compact('phone', 'externalId', 'type'));
        }
    }

    private function isBusinessAppOutgoing(array $message): bool
    {
        return ($message['from_me'] ?? false) === true
            || ($message['direction'] ?? null) === 'outbound'
            || ($message['origin'] ?? null) === 'business_app';
    }

    private function shouldAcceptStatus(string $current, ?Carbon $currentAt, string $next, Carbon $nextAt): bool
    {
        if (! $currentAt || $nextAt->gt($currentAt)) {
            return true;
        }
        if ($nextAt->lt($currentAt)) {
            return false;
        }

        $rank = ['pending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];
        return ($rank[$next] ?? 0) >= ($rank[$current] ?? 0);
    }
}
