<?php

namespace App\Services;

use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaLeadService
{
    public function fetchLead(string $leadgenId): array
    {
        $version = config('services.meta.graph_version', 'v25.0');
        $accessToken = config('services.meta.access_token');
        $url = sprintf('https://graph.facebook.com/%s/%s', $version, $leadgenId);

        Log::channel('meta_leads')->info('Graph API request.', [
            'leadgen_id' => $leadgenId,
            'url' => $url,
        ]);

        $response = Http::acceptJson()
            ->timeout(20)
            ->get($url, [
                'access_token' => $accessToken,
            ]);

        Log::channel('meta_leads')->info('Graph API response.', [
            'leadgen_id' => $leadgenId,
            'status' => $response->status(),
        ]);

        $response->throw();

        return $response->json();
    }

    public function processLeadgenValue(array $value): ?Lead
    {
        $leadgenId = (string) Arr::get($value, 'leadgen_id', '');
        if ($leadgenId === '') {
            Log::channel('meta_leads')->warning('Webhook leadgen sem leadgen_id.', ['value' => $value]);
            return null;
        }

        if (Lead::where('leadgen_id', $leadgenId)->exists()) {
            Log::channel('meta_leads')->info('Lead duplicado ignorado.', ['leadgen_id' => $leadgenId]);
            return Lead::where('leadgen_id', $leadgenId)->first();
        }

        $details = $this->fetchLead($leadgenId);
        $fields = $this->fieldDataToArray($details['field_data'] ?? []);
        $payload = $this->payload($value, $details, $fields);

        try {
            $lead = Lead::firstOrCreate(
                ['leadgen_id' => $leadgenId],
                $payload
            );
        } catch (QueryException $exception) {
            $duplicateLead = Lead::where('leadgen_id', $leadgenId)->first();
            if ($duplicateLead) {
                Log::channel('meta_leads')->info('Lead duplicado apos corrida de criacao.', ['leadgen_id' => $leadgenId]);
                return $duplicateLead;
            }

            throw $exception;
        }

        if (! $lead->wasRecentlyCreated) {
            Log::channel('meta_leads')->info('Lead duplicado apos firstOrCreate.', ['leadgen_id' => $leadgenId]);
            return $lead;
        }

        Log::channel('meta_leads')->info('Lead criado.', [
            'lead_id' => $lead->id,
            'leadgen_id' => $lead->leadgen_id,
        ]);

        $assignedUser = app(LeadAssignmentService::class)->assign($lead);
        if ($assignedUser) {
            try {
                $assignedUser->notify(new NewLeadNotification($lead));
            } catch (\Throwable $exception) {
                Log::channel('meta_leads')->error('Falha ao notificar vendedor da lead Meta.', [
                    'lead_id' => $lead->id,
                    'assigned_user_id' => $assignedUser->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $lead->fresh(['assigned_user']);
    }

    public function fieldDataToArray(array $fieldData): array
    {
        $fields = [];

        foreach ($fieldData as $field) {
            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $values = $field['values'] ?? [];
            $fields[$name] = is_array($values) ? implode(', ', array_filter($values, fn ($value) => $value !== null && $value !== '')) : (string) $values;
        }

        return $fields;
    }

    private function payload(array $webhookValue, array $details, array $fields): array
    {
        return [
            'page_id' => (string) ($webhookValue['page_id'] ?? config('services.meta.page_id')),
            'form_id' => (string) ($webhookValue['form_id'] ?? config('services.meta.form_id')),
            'ad_id' => $webhookValue['ad_id'] ?? null,
            'adgroup_id' => $webhookValue['adgroup_id'] ?? null,
            'full_name' => $this->firstFilled($fields, ['full_name', 'nome_completo', 'name']),
            'first_name' => $this->firstFilled($fields, ['first_name', 'nome']),
            'last_name' => $this->firstFilled($fields, ['last_name', 'apelido']),
            'email' => $this->firstFilled($fields, ['email', 'email_address']),
            'phone' => $this->firstFilled($fields, ['phone_number', 'phone', 'telefone', 'telemovel']),
            'vehicle_interest' => $this->firstFilled($fields, ['vehicle_interest', 'viatura', 'carro', 'modelo', 'interesse']),
            'budget' => $this->firstFilled($fields, ['budget', 'orcamento', 'orçamento']),
            'financing' => $this->firstFilled($fields, ['financing', 'financiamento']),
            'trade_in' => $this->firstFilled($fields, ['trade_in', 'retoma']),
            'raw_data' => [
                'webhook' => $webhookValue,
                'graph' => $details,
                'fields' => $fields,
            ],
            'status' => Lead::STATUS_NEW,
        ];
    }

    private function firstFilled(array $fields, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($fields[$key]) && trim((string) $fields[$key]) !== '') {
                return trim((string) $fields[$key]);
            }
        }

        return null;
    }
}
