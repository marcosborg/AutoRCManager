<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadAccessToken;
use App\Models\LeadWhatsappNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
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

        $phone = $this->normalizePhone($user->mobile_phone);
        $metadata = [
            'reason' => $phone ? null : 'missing_user_mobile_phone',
            'raw_mobile_phone' => $user->mobile_phone,
            'normalized_mobile_phone' => $phone,
        ];

        $notification = LeadWhatsappNotification::create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'access_token_id' => $accessToken->id,
            'phone' => $phone,
            'message' => $message,
            'status' => $phone ? LeadWhatsappNotification::STATUS_PENDING : LeadWhatsappNotification::STATUS_FAILED,
            'failed_at' => $phone ? null : now(),
            'metadata' => $metadata,
        ]);

        if (! $phone) {
            Log::channel('meta_leads')->warning('Vendedor sem telemovel para lead WhatsApp.', [
                'lead_id' => $lead->id,
                'assigned_user_id' => $user->id,
            ]);
        }

        return $notification;
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
