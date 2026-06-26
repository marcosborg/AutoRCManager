<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadNotification extends Notification
{
    use Queueable;

    public function __construct(private Lead $lead)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Novo lead Meta atribuido')
            ->greeting('Novo lead atribuido')
            ->line('Nome: ' . ($this->lead->full_name ?: '-'))
            ->line('Telefone: ' . ($this->lead->phone ?: '-'))
            ->line('Email: ' . ($this->lead->email ?: '-'))
            ->line('Orcamento: ' . ($this->lead->budget ?: '-'))
            ->line('Viatura/interesse: ' . ($this->lead->vehicle_interest ?: '-'))
            ->action('Abrir lead', route('admin.leads.show', $this->lead));
    }

    public function toArray($notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'leadgen_id' => $this->lead->leadgen_id,
            'name' => $this->lead->full_name,
            'phone' => $this->lead->phone,
            'email' => $this->lead->email,
            'budget' => $this->lead->budget,
            'vehicle_interest' => $this->lead->vehicle_interest,
            'url' => route('admin.leads.show', $this->lead),
        ];
    }
}
