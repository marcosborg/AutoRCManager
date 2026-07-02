<?php

namespace App\Services;

use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaInboundLeadService
{
    public function process(array $data, array $payload): Lead
    {
        $normalized = $this->normalizePayload($data, $payload);
        $leadgenId = $this->leadgenId($normalized, $payload);

        $lead = Lead::firstOrCreate(
            ['leadgen_id' => $leadgenId],
            [
                'page_id' => $normalized['page_id'],
                'form_id' => $normalized['form_id'],
                'ad_id' => $normalized['ad_id'],
                'adgroup_id' => $normalized['adgroup_id'],
                'full_name' => $normalized['full_name'],
                'first_name' => $normalized['first_name'],
                'last_name' => $normalized['last_name'],
                'email' => $normalized['email'],
                'phone' => $normalized['phone'],
                'vehicle_interest' => $normalized['vehicle_interest'],
                'budget' => $normalized['budget'],
                'financing' => $normalized['financing'],
                'trade_in' => $normalized['trade_in'],
                'raw_data' => [
                    'source' => 'inbound',
                    'payload' => $payload,
                    'purchase_timeline' => $normalized['purchase_timeline'],
                    'wants_visit' => $normalized['wants_visit'],
                ],
                'status' => Lead::STATUS_NEW,
            ]
        );

        if (! $lead->wasRecentlyCreated) {
            $this->fillMissingLeadData($lead, $normalized);
            app(AiLeadAssistantService::class)->syncFromMetaLead($lead->fresh());

            Log::channel('meta_leads')->info('Lead inbound duplicada ignorada.', [
                'lead_id' => $lead->id,
                'leadgen_id' => $lead->leadgen_id,
            ]);

            return $lead->fresh();
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

            app(LeadWhatsappNotificationService::class)->queueForLead($lead->fresh('assigned_user'), $assignedUser);
        }

        app(AiLeadAssistantService::class)->syncFromMetaLead($lead->fresh());

        Log::channel('meta_leads')->info('Lead inbound criada.', [
            'lead_id' => $lead->id,
            'leadgen_id' => $lead->leadgen_id,
            'assigned_user_id' => $assignedUser?->id,
        ]);

        return $lead->fresh(['assigned_user']);
    }

    private function leadgenId(array $data, array $payload): string
    {
        if (! empty($data['leadgen_id'])) {
            return (string) $data['leadgen_id'];
        }

        return 'inbound:' . Str::lower(sha1(json_encode(Arr::except($payload, ['token']))));
    }

    private function normalizePayload(array $data, array $payload): array
    {
        $formData = (array) ($payload['data'] ?? []);
        $fullName = $this->firstFilled([
            $data['full_name'] ?? null,
            $formData['full_name'] ?? null,
            trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
        ]);

        return [
            'leadgen_id' => $data['leadgen_id'] ?? $payload['leadgenId'] ?? null,
            'page_id' => (string) ($data['page_id'] ?? $payload['pageId'] ?? config('services.meta.page_id') ?? 'inbound'),
            'form_id' => (string) ($data['form_id'] ?? $payload['formId'] ?? config('services.meta.form_id') ?? 'inbound'),
            'ad_id' => $data['ad_id'] ?? $payload['adId'] ?? null,
            'adgroup_id' => $data['adgroup_id'] ?? $payload['adsetId'] ?? null,
            'full_name' => $fullName,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $this->firstFilled([$data['email'] ?? null, $formData['email'] ?? null]),
            'phone' => $this->firstFilled([$data['phone'] ?? null, $data['phone_number'] ?? null, $formData['phone_number'] ?? null]),
            'vehicle_interest' => $this->firstFilled([
                $data['vehicle_interest'] ?? null,
                $formData['qual_o_segmento_da_viatura_que_procura?'] ?? null,
                $payload['adName'] ?? null,
                $payload['retailerItemId'] ?? null,
            ]),
            'budget' => $this->firstFilled([
                $data['budget'] ?? null,
                $formData['qual_o_seu_orÃ§amento_aproximado?'] ?? null,
                $formData['qual_o_seu_orcamento_aproximado?'] ?? null,
            ]),
            'financing' => $this->firstFilled([
                $data['financing'] ?? null,
                $formData['como_pretende_efetuar_a_compra?_'] ?? null,
            ]),
            'trade_in' => $this->firstFilled([
                $data['trade_in'] ?? null,
                $formData['tem_viatura_para_retoma?'] ?? null,
            ]),
            'purchase_timeline' => $this->firstFilled([
                $payload['purchase_timeline'] ?? null,
                $formData['em_quanto_tempo_pretende_comprar?_'] ?? null,
            ]),
            'wants_visit' => $this->firstFilled([
                $payload['wants_visit'] ?? null,
                $formData['pretende_agendar_visita?'] ?? null,
            ]),
        ];
    }

    private function fillMissingLeadData(Lead $lead, array $normalized): void
    {
        $updates = [];

        foreach (['page_id', 'form_id', 'ad_id', 'adgroup_id', 'full_name', 'first_name', 'last_name', 'email', 'phone', 'vehicle_interest', 'budget', 'financing', 'trade_in'] as $field) {
            if (blank($lead->{$field}) && filled($normalized[$field] ?? null)) {
                $updates[$field] = $normalized[$field];
            }
        }

        if ($updates) {
            $lead->update($updates);
        }
    }

    private function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            $cleaned = $this->cleanValue($value);

            if ($cleaned !== null) {
                return $cleaned;
            }
        }

        return null;
    }

    private function cleanValue($value): ?string
    {
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map(fn ($item) => $this->cleanValue($item), $value)));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('_', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}
