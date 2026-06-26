<?php

namespace App\Services;

use App\Models\LeadWhatsappNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PendingLeadSmtpNotificationService
{
    public function send(int $limit = 50): array
    {
        $notifications = LeadWhatsappNotification::query()
            ->with(['lead', 'user'])
            ->where('status', LeadWhatsappNotification::STATUS_PENDING)
            ->where('metadata->delivery_channel', 'smtp')
            ->oldest()
            ->limit(max(1, $limit))
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            $email = $notification->metadata['recipient_email'] ?? null;

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->keepPendingAfterFailure($notification, 'Email de destino invalido.');
                $failed++;
                continue;
            }

            try {
                Mail::raw($notification->message, function ($mail) use ($notification, $email): void {
                    $name = $notification->lead?->full_name ?: 'Sem nome';
                    $mail->to($email)->subject('Nova lead atribuida: ' . $name);
                });

                $notification->update([
                    'status' => LeadWhatsappNotification::STATUS_SENT,
                    'sent_at' => now(),
                    'metadata' => array_filter(array_merge($notification->metadata ?? [], [
                        'smtp_sent' => true,
                    ])),
                ]);

                Log::channel('meta_leads')->info('Lead pendente enviada por SMTP.', [
                    'notification_id' => $notification->id,
                    'lead_id' => $notification->lead_id,
                    'recipient' => $email,
                    'type' => $notification->metadata['recipient_type'] ?? null,
                ]);

                $sent++;
            } catch (\Throwable $exception) {
                $this->keepPendingAfterFailure($notification, $exception->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $notifications->count(),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function keepPendingAfterFailure(LeadWhatsappNotification $notification, string $error): void
    {
        $metadata = $notification->metadata ?? [];
        $attempts = (int) ($metadata['smtp_attempts'] ?? 0);

        $notification->update([
            'status' => LeadWhatsappNotification::STATUS_PENDING,
            'failed_at' => null,
            'metadata' => array_filter(array_merge($metadata, [
                'smtp_sent' => false,
                'smtp_attempts' => $attempts + 1,
                'last_error' => $error,
                'last_failed_at' => now()->toDateTimeString(),
            ])),
        ]);

        Log::channel('meta_leads')->error('Falha ao enviar lead pendente por SMTP; mantida em fila.', [
            'notification_id' => $notification->id,
            'lead_id' => $notification->lead_id,
            'recipient' => $notification->metadata['recipient_email'] ?? null,
            'attempts' => $attempts + 1,
            'error' => $error,
        ]);
    }
}
