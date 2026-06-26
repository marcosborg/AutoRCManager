<?php

namespace App\Console\Commands;

use App\Models\LeadWhatsappNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPendingLeadSmtpNotifications extends Command
{
    protected $signature = 'leads:send-pending-smtp {--limit=50 : Numero maximo de notificacoes a processar}';

    protected $description = 'Envia por SMTP notificacoes de leads pendentes.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $notifications = LeadWhatsappNotification::query()
            ->with(['lead', 'user'])
            ->where('status', LeadWhatsappNotification::STATUS_PENDING)
            ->where('metadata->delivery_channel', 'smtp')
            ->oldest()
            ->limit($limit)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            $email = $notification->metadata['recipient_email'] ?? null;

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->markFailed($notification, 'Email de destino invalido.');
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
                $this->markFailed($notification, $exception->getMessage());
                $failed++;
            }
        }

        $this->info("SMTP leads enviadas: {$sent}; falhadas: {$failed}.");

        return self::SUCCESS;
    }

    private function markFailed(LeadWhatsappNotification $notification, string $error): void
    {
        $notification->update([
            'status' => LeadWhatsappNotification::STATUS_FAILED,
            'failed_at' => now(),
            'metadata' => array_filter(array_merge($notification->metadata ?? [], [
                'smtp_sent' => false,
                'error' => $error,
            ])),
        ]);

        Log::channel('meta_leads')->error('Falha ao enviar lead pendente por SMTP.', [
            'notification_id' => $notification->id,
            'lead_id' => $notification->lead_id,
            'recipient' => $notification->metadata['recipient_email'] ?? null,
            'error' => $error,
        ]);
    }
}
