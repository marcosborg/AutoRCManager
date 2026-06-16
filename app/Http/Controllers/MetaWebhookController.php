<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMetaLeadJob;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode === 'subscribe' && hash_equals((string) config('services.meta.verify_token'), (string) $token)) {
            return response((string) $challenge, Response::HTTP_OK);
        }

        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    public function receive(Request $request)
    {
        $payload = $request->all();
        Log::channel('meta_leads')->info('Webhook recebido.', ['payload' => $payload]);

        if (($payload['object'] ?? null) !== 'page') {
            Log::channel('meta_leads')->info('Webhook ignorado por object diferente de page.', [
                'object' => $payload['object'] ?? null,
            ]);

            return response()->json(['ok' => true]);
        }

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                if (($change['field'] ?? null) !== 'leadgen') {
                    continue;
                }

                $value = (array) Arr::get($change, 'value', []);
                if ($this->shouldIgnoreForm($value)) {
                    Log::channel('meta_leads')->info('Lead ignorado por form_id diferente do configurado.', [
                        'form_id' => $value['form_id'] ?? null,
                    ]);
                    continue;
                }

                ProcessMetaLeadJob::dispatch($value);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function shouldIgnoreForm(array $value): bool
    {
        $configuredFormId = config('services.meta.form_id');

        return $configuredFormId && (string) ($value['form_id'] ?? '') !== (string) $configuredFormId;
    }
}
