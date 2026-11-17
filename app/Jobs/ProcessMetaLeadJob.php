<?php

namespace App\Jobs;

use App\Services\MetaLeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMetaLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    // Graph requests are capped at 20s; keep the overall job below the
    // database queue's 90s reservation window to prevent overlapping attempts.
    public int $timeout = 75;

    public function __construct(public array $value)
    {
        $this->onQueue('meta-leads');
    }

    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function handle(MetaLeadService $service): void
    {
        try {
            $service->processLeadgenValue($this->value);
        } catch (\Throwable $exception) {
            Log::channel('meta_leads')->error('Erro ao processar lead Meta.', [
                'value' => $this->value,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
