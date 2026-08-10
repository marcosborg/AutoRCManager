<?php

namespace App\Services;

use App\Mail\LeadWhatsappFallbackMail;
use App\Models\LeadWhatsappNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadWhatsappNotificationFailureHandler
{
    public function handle(LeadWhatsappNotification $notification, ?string $reason, array $metadata = []): void
    {
        $metadata = $this->clean(array_merge($notification->metadata ?? [], $metadata, ['error' => $reason]));
        $notification->update([
            'status' => LeadWhatsappNotification::STATUS_FAILED,
            'failed_at' => now(),
            'provider_status_at' => now(),
            'metadata' => $metadata,
        ]);

        $notification->loadMissing('lead');
        $notification->lead?->where('assigned_user_id', $notification->user_id)->update([
            'seller_notification_status' => 'failed',
            'seller_notified_user_id' => null,
            'seller_notified_at' => null,
        ]);

        if (($metadata['email_fallback_sent_at'] ?? null) !== null) {
            return;
        }

        $notification->loadMissing(['lead', 'user']);
        $email = trim((string) $notification->user?->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $notification->update(['metadata' => $this->clean(array_merge($metadata, [
                'email_fallback_status' => 'skipped',
                'email_fallback_error' => 'invalid_recipient_email',
            ]))]);
            return;
        }

        try {
            Mail::to($email)->send(new LeadWhatsappFallbackMail($notification, $reason));
            $notification->update(['metadata' => $this->clean(array_merge($metadata, [
                'email_fallback_status' => 'sent',
                'email_fallback_recipient' => $email,
                'email_fallback_sent_at' => now()->toDateTimeString(),
            ]))]);
        } catch (\Throwable $exception) {
            Log::channel('meta_leads')->error('Falha no fallback email de lead.', [
                'lead_whatsapp_notification_id' => $notification->id,
                'error' => $exception->getMessage(),
            ]);
            $notification->update(['metadata' => $this->clean(array_merge($metadata, [
                'email_fallback_status' => 'failed',
                'email_fallback_recipient' => $email,
                'email_fallback_error' => $exception->getMessage(),
            ]))]);
        }
    }

    private function clean(array $metadata): array
    {
        return array_filter($metadata, fn ($value) => $value !== null && $value !== '');
    }
}
