<?php

namespace App\Mail;

use App\Models\LeadWhatsappNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadWhatsappFallbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public LeadWhatsappNotification $notification;
    public ?string $failureReason;

    public function __construct(LeadWhatsappNotification $notification, ?string $failureReason = null)
    {
        $this->notification = $notification->loadMissing(['lead', 'user']);
        $this->failureReason = $failureReason;
    }

    public function build()
    {
        $leadName = $this->notification->lead?->full_name ?: 'Sem nome';

        return $this->subject('Fallback email: nova lead atribuida - ' . $leadName)
            ->view('emails.lead_whatsapp_fallback');
    }
}
