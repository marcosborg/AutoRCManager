<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use App\Services\LeadAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MetaLeadInboundController extends Controller
{
    public function store(Request $request)
    {
        if (! $this->tokenIsValid($request)) {
            Log::channel('meta_leads')->warning('Lead inbound rejeitada por token invalido.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'leadgen_id' => ['nullable', 'string', 'max:255'],
            'page_id' => ['nullable', 'string', 'max:255'],
            'form_id' => ['nullable', 'string', 'max:255'],
            'ad_id' => ['nullable', 'string', 'max:255'],
            'adgroup_id' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'vehicle_interest' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'string', 'max:255'],
            'financing' => ['nullable', 'string', 'max:255'],
            'trade_in' => ['nullable', 'string', 'max:255'],
        ]);

        $leadgenId = $this->leadgenId($data, $request->all());
        $lead = Lead::firstOrCreate(
            ['leadgen_id' => $leadgenId],
            [
                'page_id' => (string) ($data['page_id'] ?? config('services.meta.page_id') ?? 'inbound'),
                'form_id' => (string) ($data['form_id'] ?? config('services.meta.form_id') ?? 'inbound'),
                'ad_id' => $data['ad_id'] ?? null,
                'adgroup_id' => $data['adgroup_id'] ?? null,
                'full_name' => $data['full_name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? $data['phone_number'] ?? null,
                'vehicle_interest' => $data['vehicle_interest'] ?? null,
                'budget' => $data['budget'] ?? null,
                'financing' => $data['financing'] ?? null,
                'trade_in' => $data['trade_in'] ?? null,
                'raw_data' => [
                    'source' => 'inbound',
                    'payload' => $request->all(),
                ],
                'status' => Lead::STATUS_NEW,
            ]
        );

        if (! $lead->wasRecentlyCreated) {
            Log::channel('meta_leads')->info('Lead inbound duplicada ignorada.', [
                'lead_id' => $lead->id,
                'leadgen_id' => $lead->leadgen_id,
            ]);

            return response()->json(['ok' => true, 'lead_id' => $lead->id, 'duplicate' => true]);
        }

        $assignedUser = app(LeadAssignmentService::class)->assign($lead);
        if ($assignedUser) {
            try {
                $assignedUser->notify(new NewLeadNotification($lead));
            } catch (\Throwable $exception) {
                Log::channel('meta_leads')->error('Falha ao notificar vendedor da lead inbound.', [
                    'lead_id' => $lead->id,
                    'assigned_user_id' => $assignedUser->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        Log::channel('meta_leads')->info('Lead inbound criada.', [
            'lead_id' => $lead->id,
            'leadgen_id' => $lead->leadgen_id,
            'assigned_user_id' => $assignedUser?->id,
        ]);

        return response()->json(['ok' => true, 'lead_id' => $lead->id], Response::HTTP_CREATED);
    }

    private function tokenIsValid(Request $request): bool
    {
        $configuredToken = (string) config('services.meta.inbound_token');
        if ($configuredToken === '') {
            return false;
        }

        $token = (string) ($request->bearerToken()
            ?: $request->header('X-Lead-Webhook-Token')
            ?: $request->input('token'));

        return $token !== '' && hash_equals($configuredToken, $token);
    }

    private function leadgenId(array $data, array $payload): string
    {
        if (! empty($data['leadgen_id'])) {
            return (string) $data['leadgen_id'];
        }

        return 'inbound:' . Str::lower(sha1(json_encode(Arr::except($payload, ['token']))));
    }
}
