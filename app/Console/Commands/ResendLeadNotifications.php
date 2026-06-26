<?php

namespace App\Console\Commands;

use App\Models\Lead;
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
        $query = Lead::query()
            ->with('assigned_user')
            ->whereNull('deleted_at')
            ->orderBy('created_at');

        $leadIds = array_filter(array_map('intval', (array) $this->option('lead-id')));
        if ($leadIds !== []) {
            $query->whereIn('id', $leadIds);
        } elseif ($this->option('since')) {
            $query->where('created_at', '>=', $this->option('since'));
        } else {
            $this->error('Indique --since ou --lead-id.');

            return self::FAILURE;
        }

        $sent = 0;
        $skipped = 0;

        $query->chunkById(100, function ($leads) use ($notificationService, &$sent, &$skipped): void {
            foreach ($leads as $lead) {
                if (! $lead->assigned_user) {
                    $this->warn("Lead {$lead->id} sem vendedor atribuido; ignorada.");
                    $skipped++;
                    continue;
                }

                $notificationService->queueForLead($lead, $lead->assigned_user);
                $this->info("Lead {$lead->id} colocada na fila para {$lead->assigned_user->name}.");
                $sent++;
            }
        });

        $this->info("Leads colocadas na fila: {$sent}; ignoradas: {$skipped}.");

        return self::SUCCESS;
    }
}
