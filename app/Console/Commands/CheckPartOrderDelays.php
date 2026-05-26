<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\PartOrder;
use App\Services\PartOrderNotificationService;
use Illuminate\Console\Command;

class CheckPartOrderDelays extends Command
{
    protected $signature = 'part-orders:check-delays';

    protected $description = 'Marca encomendas de pecas atrasadas.';

    public function handle(): int
    {
        $orders = PartOrder::query()
            ->whereDate('expected_delivery_date', '<', now()->toDateString())
            ->whereNull('actual_delivery_date')
            ->whereNull('delay_alert_sent_at')
            ->whereNotIn('status', ['received', 'cancelled'])
            ->get();

        foreach ($orders as $order) {
            $oldStatus = $order->status;

            $order->update([
                'status' => 'delayed',
                'delay_alert_sent_at' => now(),
            ]);

            app(PartOrderNotificationService::class)->sendDelayed($order->fresh(['vehicle.brand', 'repair', 'suplier', 'requested_by', 'technician', 'items']));

            AuditLog::create([
                'description' => 'part_order_delayed',
                'subject_id' => $order->id,
                'subject_type' => $order->getMorphClass(),
                'properties' => collect([
                    'old_status' => $oldStatus,
                    'expected_delivery_date' => $order->expected_delivery_date,
                ]),
            ]);
        }

        $this->info($orders->count() . ' encomendas atrasadas atualizadas.');

        return self::SUCCESS;
    }
}
