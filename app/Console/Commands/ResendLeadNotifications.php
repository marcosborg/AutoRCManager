<?php

namespace App\Console\Commands;

use App\Services\LeadWhatsappNotificationService;
use Illuminate\Console\Command;

class ResendLeadNotifications extends Command
{
    protected $signature = 'leads:resend-notifications
                            {--since= : Data/hora minima das leads, ex: "2026-06-25 23:00:00"}
                            {--lead-id=* : ID especifico de lead a reenviar}';

    protected $description = 'Coloca notificacoes de leads na fila usando o canal configurado.';

    public function handle(LeadWhatsappNotificationService $notificationService): int
    {
        $leadIds = array_filter(array_map('intval', (array) $this->option('lead-id')));
        $since = $this->option('since');

        if ($leadIds === [] && ! $since) {
            $this->error('Indique --since ou --lead-id.');

            return self::FAILURE;
        }

        $stats = $notificationService->resendNotifications($since, $leadIds);

        foreach ($stats['skipped_reasons'] as $reason) {
            $this->warn($reason);
        }

        foreach ($stats['errors'] as $error) {
            $this->error($error);
        }

        $this->info("Leads colocadas na fila: {$stats['queued']}; ignoradas: {$stats['skipped']}; erros: " . count($stats['errors']) . ".");
        $this->info("Notificacoes pendentes disponiveis para /api/whatsapp/lead-notifications: {$stats['pending_after']}.");

        return self::SUCCESS;
    }
}
