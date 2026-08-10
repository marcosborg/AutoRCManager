<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadAccessToken;
use App\Models\LeadWhatsappNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadWhatsappNotificationService
{
    public function resendNotifications(?string $since = null, array $leadIds = []): array
    {
        if ($since !== null) {
            throw new \InvalidArgumentException('O reenvio em massa por data foi desativado. Indique IDs de leads explícitos.');
        }

        $query = Lead::query()
            ->with('assigned_user')
            ->whereNull('deleted_at')
            ->orderBy('created_at');

        $leadIds = array_values(array_filter(array_map('intval', $leadIds)));
        if ($leadIds === []) {
            throw new \InvalidArgumentException('Indique pelo menos um ID de lead explícito.');
        }

        $query->whereIn('id', $leadIds);

        $stats = [
            'queued' => 0,
            'skipped' => 0,
            'errors' => [],
            'queued_ids' => [],
            'skipped_reasons' => [],
            'pending_after' => 0,
        ];

        Log::channel('meta_leads')->info('A iniciar reenfileiramento de notificacoes WhatsApp de leads.', [
            'since' => $since,
            'lead_ids' => $leadIds,
        ]);

        $query->chunkById(100, function ($leads) use (&$stats): void {
            foreach ($leads as $lead) {
                try {
                    if (! $lead->assigned_user) {
                        $stats['skipped']++;
                        $stats['skipped_reasons'][] = "Lead {$lead->id}: sem vendedor atribuido.";
                        continue;
                    }

                    $pending = $this->pendingNotificationFor($lead, $lead->assigned_user);
                    if ($pending) {
                        $stats['skipped']++;
                        $stats['skipped_reasons'][] = "Lead {$lead->id}: ja existe notificacao pendente {$pending->id} para {$lead->assigned_user->name}.";
                        continue;
                    }

                    $notification = $this->queueForLead($lead, $lead->assigned_user, true);
                    if ($notification && $notification->status === LeadWhatsappNotification::STATUS_PENDING && $notification->phone) {
                        $stats['queued']++;
                        $stats['queued_ids'][] = $notification->id;
                        continue;
                    }

                    $stats['errors'][] = "Lead {$lead->id}: nao foi colocada na fila; vendedor sem telemovel valido.";
                } catch (\Throwable $exception) {
                    report($exception);
                    $stats['errors'][] = "Lead {$lead->id}: {$exception->getMessage()}";
                }
            }
        });

        $stats['pending_after'] = LeadWhatsappNotification::query()
            ->where('status', LeadWhatsappNotification::STATUS_PENDING)
            ->whereNotNull('phone')
            ->count();

        Log::channel('meta_leads')->info('Reenfileiramento de notificacoes WhatsApp de leads concluido.', [
            'since' => $since,
            'queued' => $stats['queued'],
            'skipped' => $stats['skipped'],
            'errors_count' => count($stats['errors']),
            'pending_after' => $stats['pending_after'],
            'queued_ids' => $stats['queued_ids'],
        ]);

        return $stats;
    }

    public function queueForLead(Lead $lead, ?User $user = null, bool $recovered = false): ?LeadWhatsappNotification
    {
        if (config('ai_assistant.lead_delivery_channel') !== 'whatsapp') {
            Log::channel('meta_leads')->info('Notificacao WhatsApp ignorada; entrega de leads configurada por email.', [
                'lead_id' => $lead->id,
                'assigned_user_id' => $user?->id ?: $lead->assigned_user_id,
            ]);

            return null;
        }

        $lead->loadMissing('assigned_user');
        $user = $user ?: $lead->assigned_user;

        if (! $user) {
            Log::channel('meta_leads')->warning('Lead sem vendedor para notificar.', [
                'lead_id' => $lead->id,
            ]);

            return null;
        }

        $deliveryKey = "lead:{$lead->id}:user:{$user->id}";

        return DB::transaction(function () use ($lead, $user, $recovered, $deliveryKey) {
            $existing = LeadWhatsappNotification::query()->where('delivery_key', $deliveryKey)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $plainToken = Str::random(72);
            $assignmentHistoryId = $lead->assignment_histories()->where('user_id', $user->id)->latest('id')->value('id');
            $accessToken = LeadAccessToken::create([
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'assignment_history_id' => $assignmentHistoryId,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addDays(7),
                'first_open_deadline_at' => now()->addHour(),
            ]);

            $phone = $this->normalizePhone($user->mobile_phone);
            $scheduledFor = $this->scheduledFor($user->id, $recovered);
            $notification = LeadWhatsappNotification::create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'access_token_id' => $accessToken->id,
            'phone' => $phone,
            'message' => $this->messageFor($lead, $user, $plainToken),
            'status' => $phone ? LeadWhatsappNotification::STATUS_PENDING : LeadWhatsappNotification::STATUS_FAILED,
            'delivery_key' => $deliveryKey,
            'scheduled_for' => $scheduledFor,
            'failed_at' => $phone ? null : now(),
            'metadata' => [
                'reason' => $phone ? null : 'missing_user_mobile_phone',
                'raw_mobile_phone' => $user->mobile_phone,
                'normalized_mobile_phone' => $phone,
                'recovered' => $recovered,
            ],
            ]);

            if ((int) $lead->assigned_user_id === (int) $user->id) {
                $lead->update([
                    'seller_notification_status' => $phone ? 'pending' : 'failed',
                    'seller_notified_user_id' => null,
                    'seller_notified_at' => null,
                ]);
            }

            if (! $phone) {
                Log::channel('meta_leads')->warning('Vendedor sem telemovel para lead WhatsApp.', [
                    'lead_id' => $lead->id,
                    'assigned_user_id' => $user->id,
                ]);
            }

            return $notification;
        });
    }

    public function pendingNotificationFor(Lead $lead, User $user): ?LeadWhatsappNotification
    {
        return LeadWhatsappNotification::query()
            ->where('lead_id', $lead->id)
            ->where('user_id', $user->id)
            ->where('status', LeadWhatsappNotification::STATUS_PENDING)
            ->first();
    }

    private function scheduledFor(int $userId, bool $recovered): \Illuminate\Support\Carbon
    {
        $now = now();
        if (! $recovered) {
            return $now;
        }

        $lastScheduled = LeadWhatsappNotification::query()
            ->where('user_id', $userId)
            ->where('status', LeadWhatsappNotification::STATUS_PENDING)
            ->whereNotNull('scheduled_for')
            ->lockForUpdate()
            ->max('scheduled_for');

        return $lastScheduled && \Illuminate\Support\Carbon::parse($lastScheduled)->gte($now)
            ? \Illuminate\Support\Carbon::parse($lastScheduled)->addMinute()
            : $now;
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
