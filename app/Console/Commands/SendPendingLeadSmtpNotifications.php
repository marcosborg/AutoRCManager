<?php

namespace App\Console\Commands;

use App\Services\PendingLeadSmtpNotificationService;
use Illuminate\Console\Command;

class SendPendingLeadSmtpNotifications extends Command
{
    protected $signature = 'leads:send-pending-smtp {--limit=50 : Numero maximo de notificacoes a processar}';

    protected $description = 'Envia por SMTP notificacoes de leads pendentes.';

    public function handle(PendingLeadSmtpNotificationService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $result = $service->send($limit);

        $this->info("SMTP leads processadas: {$result['processed']}; enviadas: {$result['sent']}; falhadas: {$result['failed']}.");

        return self::SUCCESS;
    }
}
