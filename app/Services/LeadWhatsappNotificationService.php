<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadAccessToken;
use App\Models\LeadWhatsappNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LeadWhatsappNotificationService
{
    public function queueForLead(Lead $lead, ?User $user = null): ?LeadWhatsappNotification
    {
        $lead->loadMissing('assigned_user');
        $user = $user ?: $lead->assigned_user;

        if (! $user) {
            Log::channel('meta_leads')->warning('Lead sem vendedor para notificar.', [
                'lead_id' => $lead->id,
            ]);

            return null;
        }

        $plainToken = Str::random(72);
        $accessToken = LeadAccessToken::create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
            'first_open_deadline_at' => now()->addHour(),
        ]);

        $message = $this->messageFor($lead, $user, $plainToken);

        if ($this->deliveryChannel() === 'smtp') {
            $this->sendLeadEmails($lead, $user, $message, $accessToken->id);

            return null;
        }

        $phone = $this->normalizePhone($user->mobile_phone);
        $notification = LeadWhatsappNotification::create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'access_token_id' => $accessToken->id,
            'phone' => $phone,
            'message' => $message,
            'status' => $phone ? LeadWhatsappNotification::STATUS_PENDING : LeadWhatsappNotification::STATUS_FAILED,
            'failed_at' => $phone ? null : now(),
            'metadata' => [
                'reason' => $phone ? null : 'missing_user_mobile_phone',
                'raw_mobile_phone' => $user->mobile_phone,
                'normalized_mobile_phone' => $phone,
            ],
        ]);

        if (! $phone) {
            Log::channel('meta_leads')->warning('Vendedor sem telemovel para lead WhatsApp.', [
                'lead_id' => $lead->id,
                'assigned_user_id' => $user->id,
            ]);
        } else {
            $this->queueCcNotifications($lead, $user, $plainToken, $accessToken->id);
        }

        return $notification;
    }

    private function queueCcNotifications(Lead $lead, User $user, string $plainToken, int $accessTokenId): void
    {
        foreach ($this->ccPhones() as $ccPhone) {
            $phone = $this->normalizePhone($ccPhone);

            if (! $phone || $phone === $this->normalizePhone($user->mobile_phone)) {
                continue;
            }

            LeadWhatsappNotification::create([
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'access_token_id' => $accessTokenId,
                'phone' => $phone,
                'message' => $this->messageFor($lead, $user, $plainToken),
                'status' => LeadWhatsappNotification::STATUS_PENDING,
                'metadata' => [
                    'type' => 'stand_seller_copy',
                    'original_user_id' => $user->id,
                    'original_user_name' => $user->name,
                ],
            ]);
        }
    }

    private function sendLeadEmails(Lead $lead, User $user, string $message, int $accessTokenId): void
    {
        $subject = 'Nova lead atribuida: ' . ($lead->full_name ?: 'Sem nome');
        $recipients = [];

        if ($this->validEmail($user->email)) {
            $recipients[] = [
                'email' => $user->email,
                'type' => 'assigned_stand',
            ];
        } else {
            Log::channel('meta_leads')->warning('Vendedor sem email valido para lead SMTP.', [
                'lead_id' => $lead->id,
                'assigned_user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        foreach ($this->ccEmails() as $email) {
            if ($this->validEmail($email) && strcasecmp($email, (string) $user->email) !== 0) {
                $recipients[] = [
                    'email' => $email,
                    'type' => 'stand_seller_copy',
                ];
            }
        }

        foreach ($recipients as $recipient) {
            Mail::raw($message, function ($mail) use ($recipient, $subject): void {
                $mail->to($recipient['email'])->subject($subject);
            });

            Log::channel('meta_leads')->info('Lead enviada por SMTP.', [
                'lead_id' => $lead->id,
                'assigned_user_id' => $user->id,
                'access_token_id' => $accessTokenId,
                'recipient' => $recipient['email'],
                'type' => $recipient['type'],
            ]);
        }
    }

    private function deliveryChannel(): string
    {
        $channel = strtolower((string) config('ai_assistant.lead_delivery_channel', 'whatsapp'));

        return in_array($channel, ['smtp', 'mail', 'email'], true) ? 'smtp' : 'whatsapp';
    }

    private function ccEmails(): array
    {
        return collect(config('ai_assistant.lead_email_cc_addresses', []))
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique(fn ($email) => strtolower($email))
            ->values()
            ->all();
    }

    private function validEmail(?string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function ccPhones(): array
    {
        return collect(config('ai_assistant.lead_whatsapp_cc_phones', []))
            ->map(fn ($phone) => $this->normalizePhone($phone))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function messageFor(Lead $lead, User $user, string $plainToken): string
    {
        $url = $this->leadAccessUrl($plainToken);
        $name = $lead->full_name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: 'Sem nome';
        $phone = $lead->phone ?: '-';
        $interest = $lead->vehicle_interest ?: '-';
        $budget = $lead->budget ?: '-';
        $financing = $lead->financing ?: '-';
        $tradeIn = $lead->trade_in ?: '-';

        return implode("\n", [
            "Nova lead atribuida: {$name}",
            "Telefone: {$phone}",
            "Interesse: {$interest}",
            "Orcamento: {$budget}",
            "Compra: {$financing}",
            "Retoma: {$tradeIn}",
            'Abrir lead:',
            $url,
            '',
            'Abra no prazo de 1 hora. Depois de aberto, o link fica valido por 7 dias.',
        ]);
    }

    private function leadAccessUrl(string $plainToken): string
    {
        $baseUrl = rtrim((string) config('app.lead_access_base_url'), '/');
        $path = route('lead-access.show', ['token' => $plainToken], false);

        return $baseUrl . $path;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            return '351' . $digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '3519')) {
            return $digits;
        }

        return null;
    }
}
