<?php

namespace App\Console\Commands;

use App\Services\LeadAccessEscalationService;
use Illuminate\Console\Command;

class ExpireUnopenedLeadAccessTokens extends Command
{
    protected $signature = 'leads:expire-unopened-access {--limit=50 : Numero maximo de links a processar}';

    protected $description = 'Expira links de leads nao abertos e transita a lead para o proximo vendedor.';

    public function handle(LeadAccessEscalationService $escalationService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $expired = $escalationService->expireUnopenedTokens($limit);

        $this->info("Links expirados: {$expired}");

        return self::SUCCESS;
    }
}
