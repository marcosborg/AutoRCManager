<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMetaInboundLeadJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $payload = $request->all();

        ProcessMetaInboundLeadJob::dispatch($data, $payload);

        Log::channel('meta_leads')->info('Lead inbound colocada na fila.', [
            'leadgen_id' => $data['leadgen_id'] ?? $payload['leadgenId'] ?? null,
        ]);

        return response()->json(['ok' => true, 'queued' => true], Response::HTTP_ACCEPTED);
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
}
