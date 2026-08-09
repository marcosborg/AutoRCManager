<?php

namespace App\Jobs;

use App\Models\LeadWhatsappNotification;
use App\Services\WhatsappCloudApi;
use App\Services\LeadWhatsappNotificationFailureHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsappLeadNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $notificationId)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsappCloudApi $api): void
    {
        if (! config('whatsapp.lead_notifications_enabled')) {
            Log::channel('meta_leads')->warning('Envio WhatsApp de lead bloqueado pelo interruptor de emergência.', [
                'lead_whatsapp_notification_id' => $this->notificationId,
            ]);

            return;
        }

        $notification = LeadWhatsappNotification::with(['lead', 'user'])->find($this->notificationId);
        if (! $notification || $notification->status !== LeadWhatsappNotification::STATUS_PENDING || ! $notification->phone) {
            return;
        }

        $template = config('whatsapp.templates.seller_lead');
        preg_match('/https?:\/\/\S+/', $notification->message, $urlMatch);
        $buttonValue = isset($urlMatch[0]) ? basename(parse_url($urlMatch[0], PHP_URL_PATH)) : null;
        $result = $api->sendTemplate($notification->phone, $template['name'], $template['language'], [
            $notification->user?->name ?: 'Comercial',
            $notification->lead?->full_name ?: 'Sem nome',
            $notification->lead?->phone ?: '-',
            $notification->lead?->vehicle_interest ?: '-',
            $notification->lead?->budget ?: '-',
        ], $buttonValue);

        $notification->update([
            'status' => LeadWhatsappNotification::STATUS_SENT,
            'external_id' => data_get($result, 'messages.0.id'),
            'sent_at' => now(),
            'provider_status_at' => now(),
            'metadata' => array_merge($notification->metadata ?? [], ['transport' => 'cloud', 'cloud_response' => $result]),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $notification = LeadWhatsappNotification::find($this->notificationId);
        if (! $notification || $notification->status !== LeadWhatsappNotification::STATUS_PENDING) {
            return;
        }

        app(LeadWhatsappNotificationFailureHandler::class)->handle(
            $notification,
            $exception->getMessage(),
            ['transport' => 'cloud']
        );
    }
}
