<?php

namespace App\Console\Commands;

use App\Services\LeadWhatsappNotificationService;
use Illuminate\Console\Command;

class ResendLeadNotifications extends Command
{
    protected $signature = 'leads:resend-notifications
                            {--lead-id=* : ID especifico de lead a reenviar}';

    protected $description = 'Coloca notificacoes de leads na fila usando o canal configurado.';

    public function handle(LeadWhatsappNotificationService $notificationService): int
    {
        $leadIds = array_filter(array_map('intval', (array) $this->option('lead-id')));
        if ($leadIds === []) {
            $this->error('Indique pelo menos uma opção --lead-id. O reenvio em massa por data está desativado.');

            return self::FAILURE;
        }

        $stats = $notificationService->resendNotifications(null, $leadIds);

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
