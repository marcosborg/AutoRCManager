<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadSalesRotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadAssignmentService
{
    public function assign(Lead $lead, array $excludeUserIds = [], string $reason = 'round_robin'): ?User
    {
        return DB::transaction(function () use ($lead, $excludeUserIds, $reason) {
            $excludeUserIds = array_values(array_unique(array_filter(array_map('intval', $excludeUserIds))));

            $salespeople = User::query()
                ->whereHas('roles', fn ($query) => $query->where('title', 'Stand'))
                ->whereNotNull('mobile_phone')
                ->where('mobile_phone', '!=', '')
                ->when($excludeUserIds !== [], fn ($query) => $query->whereNotIn('id', $excludeUserIds))
                ->orderBy('id')
                ->get();

            if ($salespeople->isEmpty()) {
                Log::channel('meta_leads')->warning('Lead sem vendedor Stand disponivel para atribuicao.', [
                    'lead_id' => $lead->id,
                    'leadgen_id' => $lead->leadgen_id,
                    'excluded_user_ids' => $excludeUserIds,
                ]);

                return null;
            }

            $rotation = LeadSalesRotation::query()->lockForUpdate()->first();
            if (! $rotation) {
                $rotation = LeadSalesRotation::query()->create();
            }
            $nextUser = $this->nextUser($salespeople, $rotation->last_user_id);

            $lead->update(['assigned_user_id' => $nextUser->id]);
            $lead->assignment_histories()->create([
                'user_id' => $nextUser->id,
                'reason' => $reason,
            ]);

            $rotation->update(['last_user_id' => $nextUser->id]);

            Log::channel('meta_leads')->info('Lead atribuido.', [
                'lead_id' => $lead->id,
                'leadgen_id' => $lead->leadgen_id,
                'assigned_user_id' => $nextUser->id,
                'reason' => $reason,
            ]);

            return $nextUser;
        });
    }

    private function nextUser($salespeople, ?int $lastUserId): User
    {
        if (! $lastUserId) {
            return $salespeople->first();
        }

        $lastIndex = $salespeople->search(fn (User $user) => (int) $user->id === (int) $lastUserId);

        if ($lastIndex === false) {
            return $salespeople->first();
        }

        return $salespeople->get(($lastIndex + 1) % $salespeople->count());
    }
}
