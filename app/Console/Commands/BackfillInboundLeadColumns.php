<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;

class BackfillInboundLeadColumns extends Command
{
    protected $signature = 'leads:backfill-inbound {--dry-run : Show changes without saving}';

    protected $description = 'Preenche colunas de leads inbound a partir do raw_data.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        Lead::query()
            ->whereNotNull('raw_data')
            ->orderBy('id')
            ->chunkById(100, function ($leads) use ($dryRun, &$updated) {
                foreach ($leads as $lead) {
                    $payload = (array) data_get($lead->raw_data, 'payload', []);
                    if (! $payload) {
                        continue;
                    }

                    $normalized = $this->normalizePayload($payload);
                    $changes = $this->changesFor($lead, $normalized);

                    if (! $changes) {
                        continue;
                    }

                    $updated++;
                    $this->line(sprintf(
                        '%s lead #%d: %s',
                        $dryRun ? 'Would update' : 'Updating',
                        $lead->id,
                        implode(', ', array_keys($changes))
                    ));

                    if (! $dryRun) {
                        $lead->update($changes);
                    }
                }
            });

        $this->info(sprintf('%d leads %s.', $updated, $dryRun ? 'would be updated' : 'updated'));

        return self::SUCCESS;
    }

    private function changesFor(Lead $lead, array $normalized): array
    {
        $changes = [];

        if (
            str_starts_with((string) $lead->leadgen_id, 'inbound:')
            && filled($normalized['leadgen_id'])
            && ! Lead::where('leadgen_id', $normalized['leadgen_id'])->whereKeyNot($lead->id)->exists()
        ) {
            $changes['leadgen_id'] = $normalized['leadgen_id'];
        }

        foreach (['page_id', 'form_id', 'ad_id', 'adgroup_id', 'full_name', 'email', 'phone', 'vehicle_interest', 'budget', 'financing', 'trade_in'] as $field) {
            if (blank($lead->{$field}) && filled($normalized[$field] ?? null)) {
                $changes[$field] = $normalized[$field];
            }
        }

        return $changes;
    }

    private function normalizePayload(array $payload): array
    {
        $formData = (array) ($payload['data'] ?? []);

        return [
            'leadgen_id' => $payload['leadgenId'] ?? null,
            'page_id' => (string) ($payload['pageId'] ?? config('services.meta.page_id') ?? 'inbound'),
            'form_id' => (string) ($payload['formId'] ?? config('services.meta.form_id') ?? 'inbound'),
            'ad_id' => $payload['adId'] ?? null,
            'adgroup_id' => $payload['adsetId'] ?? null,
            'full_name' => $this->firstFilled([$formData['full_name'] ?? null]),
            'email' => $this->firstFilled([$formData['email'] ?? null]),
            'phone' => $this->firstFilled([$formData['phone_number'] ?? null]),
            'vehicle_interest' => $this->firstFilled([
                $formData['qual_o_segmento_da_viatura_que_procura?'] ?? null,
                $payload['adName'] ?? null,
                $payload['retailerItemId'] ?? null,
            ]),
            'budget' => $this->firstFilled([
                $formData['qual_o_seu_orçamento_aproximado?'] ?? null,
                $formData['qual_o_seu_orcamento_aproximado?'] ?? null,
            ]),
            'financing' => $this->firstFilled([$formData['como_pretende_efetuar_a_compra?_'] ?? null]),
            'trade_in' => $this->firstFilled([$formData['tem_viatura_para_retoma?'] ?? null]),
        ];
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
