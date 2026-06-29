<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendPendingLeadSmtpNotifications extends Command
{
    protected $signature = 'leads:send-pending-smtp {--limit=50 : Numero maximo de notificacoes a processar}';

    protected $description = 'Comando desativado: as leads sao entregues por WhatsApp.';

    public function handle(): int
    {
        $this->warn('Envio SMTP de leads desativado. As leads sao colocadas na fila de WhatsApp.');

        return self::SUCCESS;
    }
}
