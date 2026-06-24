<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadAccessEscalationService
{
    public const REVOKED_NO_OPEN_TIMEOUT = 'no_open_timeout';

    public function expireUnopenedTokens(int $limit = 50): int
    {
        $tokenIds = LeadAccessToken::query()
            ->whereNull('last_used_at')
            ->whereNull('revoked_at')
            ->whereNotNull('first_open_deadline_at')
            ->where('first_open_deadline_at', '<=', now())
            ->oldest('first_open_deadline_at')
            ->limit($limit)
            ->pluck('id');

        $expired = 0;
        foreach ($tokenIds as $tokenId) {
            if ($this->expireTokenAndReassign((int) $tokenId)) {
                $expired++;
            }
        }

        return $expired;
    }

    public function expireTokenAndReassign(int|LeadAccessToken $token): bool
    {
        $tokenId = $token instanceof LeadAccessToken ? $token->id : $token;

        return (bool) DB::transaction(function () use ($tokenId) {
            $accessToken = LeadAccessToken::query()
                ->with('lead')
                ->lockForUpdate()
                ->find($tokenId);

            if (! $accessToken || ! $accessToken->firstOpenDeadlinePassed() || $accessToken->revoked_at) {
                return false;
            }

            $accessToken->update([
                'revoked_at' => now(),
                'revoked_reason' => self::REVOKED_NO_OPEN_TIMEOUT,
            ]);

            $lead = Lead::query()->lockForUpdate()->find($accessToken->lead_id);
            if (! $lead) {
                return true;
            }

            if ($this->leadHasAnotherUsableToken($lead, $accessToken)) {
                return true;
            }

            $excludedUserIds = $this->excludedUserIdsForLead($lead, $accessToken->user_id);
            $assignedUser = app(LeadAssignmentService::class)->assign(
                $lead,
                $excludedUserIds,
                self::REVOKED_NO_OPEN_TIMEOUT
            );

            if (! $assignedUser) {
                Log::channel('meta_leads')->info('Lead completou volta de vendedores; a reiniciar rotacao.', [
                    'lead_id' => $lead->id,
                    'expired_access_token_id' => $accessToken->id,
                    'excluded_user_ids' => $excludedUserIds,
                ]);

                $assignedUser = app(LeadAssignmentService::class)->assign(
                    $lead,
                    [],
                    self::REVOKED_NO_OPEN_TIMEOUT
                );

                if (! $assignedUser) {
                    Log::channel('meta_leads')->warning('Lead nao transitou por falta de vendedor com telemovel.', [
                        'lead_id' => $lead->id,
                        'expired_access_token_id' => $accessToken->id,
                        'excluded_user_ids' => $excludedUserIds,
                    ]);

                    return true;
                }
            }

            app(LeadWhatsappNotificationService::class)->queueForLead($lead->fresh('assigned_user'), $assignedUser);

            Log::channel('meta_leads')->info('Lead transitada por link nao aberto.', [
                'lead_id' => $lead->id,
                'expired_access_token_id' => $accessToken->id,
                'from_user_id' => $accessToken->user_id,
                'to_user_id' => $assignedUser->id,
            ]);

            return true;
        });
    }

    private function leadHasAnotherUsableToken(Lead $lead, LeadAccessToken $expiredToken): bool
    {
        return LeadAccessToken::query()
            ->where('lead_id', $lead->id)
            ->where('id', '!=', $expiredToken->id)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNotNull('last_used_at')
                    ->orWhere('first_open_deadline_at', '>', now());
            })
            ->exists();
    }

    private function excludedUserIdsForLead(Lead $lead, ?int $currentUserId): array
    {
        return LeadAccessToken::query()
            ->where('lead_id', $lead->id)
            ->where(function ($query) use ($currentUserId) {
                $query->where('revoked_reason', self::REVOKED_NO_OPEN_TIMEOUT)
                    ->when($currentUserId, fn ($query) => $query->orWhere('user_id', $currentUserId));
            })
            ->pluck('user_id')
            ->filter()
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values()
            ->all();
    }
}
