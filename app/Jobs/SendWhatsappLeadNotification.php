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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsappLeadNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Waiting for another worker is not a delivery failure. Limit actual
    // exceptions instead so a retained lock cannot exhaust the queue attempts.
    public int $tries = 0;
    public int $maxExceptions = 4;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $notificationId)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsappCloudApi $api): void
    {
        // The cPanel workers share this host's file store, independently of the
        // default cache driver. Re-read the notification only after acquiring it.
        $lock = Cache::store('file')->lock('whatsapp-lead-notification:'.$this->notificationId, 180);
        if (! $lock->get()) {
            $this->release(15);
            return;
        }
        try {
            $this->process($api);
        } finally {
            $lock->release();
        }
    }

    private function process(WhatsappCloudApi $api): void
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

        if ($this->skipObsoleteRecipient($notification)) {
            return;
        }

        if ($notification->scheduled_for && $notification->scheduled_for->isFuture()) {
            $this->release(max(1, now()->diffInSeconds($notification->scheduled_for)));
            return;
        }

        $notification->increment('attempts');
        $notification->update(['attempted_at' => now()]);

        $template = config('whatsapp.templates.seller_lead');
        preg_match('/https?:\/\/\S+/', $notification->message, $urlMatch);
        $buttonValue = isset($urlMatch[0]) ? basename(parse_url($urlMatch[0], PHP_URL_PATH)) : null;
        try {
            $result = $api->sendTemplate($notification->phone, $template['name'], $template['language'], [
                $notification->user?->name ?: 'Comercial',
                $notification->lead?->full_name ?: 'Sem nome',
                $notification->lead?->phone ?: '-',
                $notification->lead?->vehicle_interest ?: '-',
                $notification->lead?->budget ?: '-',
            ], $buttonValue);
        } catch (Throwable $exception) {
            // A token invalidado nunca recupera com tentativas. Em vez de manter a
            // lead escondida na fila, registamos a falha e notificamos o vendedor
            // imediatamente por e-mail.
            if ($this->isAuthenticationFailure($exception)) {
                app(LeadWhatsappNotificationFailureHandler::class)->handle(
                    $notification,
                    $exception->getMessage(),
                    ['transport' => 'cloud', 'failure_kind' => 'authentication']
                );

                return;
            }

            throw $exception;
        }

        $notification->update([
            'status' => LeadWhatsappNotification::STATUS_SENT,
            'external_id' => data_get($result, 'messages.0.id'),
            'sent_at' => now(),
            'provider_status_at' => now(),
            'metadata' => array_merge($notification->metadata ?? [], ['transport' => 'cloud', 'cloud_response' => $result]),
        ]);

        $notification->lead?->newQuery()->whereKey($notification->lead_id)->where('assigned_user_id', $notification->user_id)->update([
            'seller_notification_status' => 'sent',
            'seller_notified_user_id' => $notification->user_id,
            'seller_notified_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $notification = LeadWhatsappNotification::with(['lead', 'user'])->find($this->notificationId);
        if (! $notification || $notification->status !== LeadWhatsappNotification::STATUS_PENDING) {
            return;
        }

        if ($this->skipObsoleteRecipient($notification)) {
            return;
        }

        app(LeadWhatsappNotificationFailureHandler::class)->handle(
            $notification,
            $exception->getMessage(),
            ['transport' => 'cloud']
        );
    }

    private function skipObsoleteRecipient(LeadWhatsappNotification $notification): bool
    {
        if ($notification->lead && $notification->user
            && (int) $notification->lead->assigned_user_id === (int) $notification->user_id) {
            return false;
        }

        // Keep the original notification as an audit record, but never send it
        // (or its e-mail fallback) to a seller who no longer owns the lead.
        $notification->update([
            'status' => LeadWhatsappNotification::STATUS_FAILED,
            'failed_at' => now(),
            'metadata' => array_merge($notification->metadata ?? [], [
                'delivery_status' => 'skipped',
                'failure_kind' => 'recipient_changed',
                'failure_reason' => 'Envio ignorado: a lead já não está atribuída a este vendedor ou o destinatário deixou de estar disponível.',
                'skipped_at' => now()->toIso8601String(),
                'current_assigned_user_id' => $notification->lead?->assigned_user_id,
            ]),
        ]);

        return true;
    }

    private function isAuthenticationFailure(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'whatsapp cloud api error (401)')
            || str_contains($message, 'authentication error')
            || str_contains($message, 'invalid oauth access token');
    }
}
