<?php

namespace App\Console\Commands;

use App\Models\LeadWhatsappNotification;
use App\Services\LeadWhatsappNotificationFailureHandler;
use Illuminate\Console\Command;

class FailUndeliveredWhatsappLeadNotifications extends Command
{
    protected $signature = 'leads:fail-undelivered-whatsapp {--limit=100 : Numero maximo de notificacoes a processar}';

    protected $description = 'Aplica contingencia quando a Meta aceitou uma lead mas nao confirmou a entrega no WhatsApp.';

    public function handle(LeadWhatsappNotificationFailureHandler $failureHandler): int
    {
        $minutes = max(1, (int) config('whatsapp.delivery_timeout_minutes', 15));
        $cutoff = now()->subMinutes($minutes);

        $notifications = LeadWhatsappNotification::query()
            ->where('status', LeadWhatsappNotification::STATUS_SENT)
            ->whereNotNull('external_id')
            ->where('provider_status_at', '<=', $cutoff)
            ->orderBy('provider_status_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $failed = 0;
        foreach ($notifications as $notification) {
            $deliveryStatus = (string) data_get($notification->metadata, 'delivery_status', 'sent');
            if (in_array($deliveryStatus, ['delivered', 'read'], true)) {
                continue;
            }

            $failureHandler->handle(
                $notification,
                "WhatsApp Cloud API did not confirm delivery within {$minutes} minutes.",
                [
                    'transport' => 'cloud',
                    'failure_kind' => 'delivery_timeout',
                    'delivery_status' => $deliveryStatus,
                ]
            );
            $failed++;
        }

        $this->info("Notificacoes sem confirmacao de entrega tratadas: {$failed}.");

        return self::SUCCESS;
    }
}
