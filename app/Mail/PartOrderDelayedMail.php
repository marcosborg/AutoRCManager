<?php

namespace App\Mail;

use App\Models\PartOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartOrderDelayedMail extends Mailable
{
    use Queueable, SerializesModels;

    public PartOrder $partOrder;

    public function __construct(PartOrder $partOrder)
    {
        $this->partOrder = $partOrder->loadMissing(['vehicle.brand', 'repair', 'suplier', 'requested_by', 'technician', 'items']);
    }

    public function build()
    {
        return $this->subject('Encomenda de pecas atrasada #' . $this->partOrder->id)
            ->view('emails.part_order_delayed');
    }
}
