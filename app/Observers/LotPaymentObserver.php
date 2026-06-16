<?php

namespace App\Observers;

use App\Models\LotPayment;
use App\Services\ManagementAlertService;

class LotPaymentObserver
{
    public function created(LotPayment $payment): void
    {
        app(ManagementAlertService::class)->lotPaymentPending($payment);
    }

    public function updated(LotPayment $payment): void
    {
        if ($payment->isDirty('approval_status')) {
            app(ManagementAlertService::class)->lotPaymentPending($payment);
        }
    }
}
