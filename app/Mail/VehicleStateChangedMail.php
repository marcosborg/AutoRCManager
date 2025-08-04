<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VehicleStateChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $license;
    public $messageText;

    public function __construct($license, $messageText)
    {
        $this->license = $license;
        $this->messageText = $messageText;
    }

    public function build()
    {
        return $this->subject('Mudança de Estado da Viatura')
                    ->view('emails.vehicle_state_changed')
                    ->with([
                        'license' => $this->license,
                        'messageText' => $this->messageText,
                    ]);
    }
}
