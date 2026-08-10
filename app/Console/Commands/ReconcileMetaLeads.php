<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMetaLeadJob;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconcileMetaLeads extends Command
{
    protected $signature = 'meta:reconcile-leads
        {--dry-run : Apenas mostra as leads em falta, sem as colocar na fila}
        {--minutes= : Janela de recuperação em minutos}';

    protected $description = 'Recupera de forma idempotente leads recentes que a Meta não entregou por webhook';

    public function handle(): int
    {
        $configuration = config('services.meta.lead_reconciliation', []);

        if (! ($configuration['enabled'] ?? false)) {
            $this->line('Reconciliação Meta desactivada.');

            return self::SUCCESS;
        }

        $token = (string) config('services.meta.access_token');
        $formIds = $configuration['form_ids'] ?? [];
        if ($token === '' || $formIds === []) {
            $this->error('Falta o token de acesso Meta ou pelo menos um formulário configurado.');

            return self::FAILURE;
        }

        $minutes = max(1, (int) ($this->option('minutes') ?: ($configuration['lookback_minutes'] ?? 1440)));
        $since = now()->subMinutes($minutes);
        $pageSize = min(100, max(1, (int) ($configuration['page_size'] ?? 100)));
        $maxPages = min(100, max(1, (int) ($configuration['max_pages_per_form'] ?? 10)));
        $dryRun = (bool) $this->option('dry-run');
        $stats = ['seen' => 0, 'existing' => 0, 'queued' => 0, 'invalid' => 0, 'old' => 0];

        foreach ($formIds as $formId) {
            $nextUrl = $this->leadEdgeUrl((string) $formId);

            for ($page = 1; $page <= $maxPages && $nextUrl; $page++) {
                $response = Http::acceptJson()->timeout(20)->retry(2, 500)->get(
                    $nextUrl,
                    $page === 1 ? [
                        'access_token' => $token,
                        'fields' => 'id,created_time,ad_id,adgroup_id',
                        'limit' => $pageSize,
                    ] : []
                );

                if ($response->failed()) {
                    Log::channel('meta_leads')->error('Falha ao reconciliar leads Meta.', [
                        'form_id' => $formId,
                        'status' => $response->status(),
                    ]);
                    $this->error("Meta respondeu {$response->status()} para o formulário {$formId}.");

                    return self::FAILURE;
                }

                foreach ($response->json('data', []) as $item) {
                    $stats['seen']++;
                    $leadgenId = (string) ($item['id'] ?? '');
                    $createdAt = $this->createdAt($item['created_time'] ?? null);

                    if ($leadgenId === '' || ! $createdAt) {
                        $stats['invalid']++;
                        continue;
                    }

                    if ($createdAt->lt($since)) {
                        $stats['old']++;
                        continue;
                    }

                    if (Lead::where('leadgen_id', $leadgenId)->exists()) {
                        $stats['existing']++;
                        continue;
                    }

                    $value = [
                        'leadgen_id' => $leadgenId,
                        'page_id' => config('services.meta.page_id'),
                        'form_id' => (string) $formId,
                        'ad_id' => $item['ad_id'] ?? null,
                        'adgroup_id' => $item['adgroup_id'] ?? null,
                        'created_time' => $createdAt->toIso8601String(),
                        'recovered' => true,
                    ];

                    if (! $dryRun) {
                        ProcessMetaLeadJob::dispatch($value);
                    }

                    $stats['queued']++;
                }

                $nextUrl = data_get($response->json(), 'paging.next');
            }
        }

        Log::channel('meta_leads')->info('Reconciliação Meta concluída.', $stats + [
            'since' => $since->toIso8601String(),
            'dry_run' => $dryRun,
        ]);

        $this->info(sprintf(
            'Reconciliação concluída: %d vistas, %d já existentes, %d %s, %d antigas.',
            $stats['seen'],
            $stats['existing'],
            $stats['queued'],
            $dryRun ? 'em falta' : 'colocadas na fila',
            $stats['old'],
        ));

        return self::SUCCESS;
    }

    private function leadEdgeUrl(string $formId): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/leads',
            config('services.meta.graph_version', 'v25.0'),
            $formId,
        );
    }

    private function createdAt(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
