<?php

namespace App\Console\Commands;

use App\Models\PartPayment;
use Illuminate\Console\Command;

class CheckPartPaymentOverdue extends Command
{
    protected $signature = 'part-payments:check-overdue';

    protected $description = 'Marca pagamentos de pecas vencidos.';

    public function handle(): int
    {
        $count = PartPayment::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('payment_status', ['paid', 'cancelled'])
            ->update(['payment_status' => 'overdue']);

        $this->info($count . ' pagamentos vencidos atualizados.');

        return self::SUCCESS;
    }
}
