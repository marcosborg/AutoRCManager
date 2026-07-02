<?php

namespace App\Jobs;

use App\Services\MetaInboundLeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMetaInboundLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public array $data, public array $payload)
    {
        $this->onQueue('meta-leads');
    }

    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function handle(MetaInboundLeadService $service): void
    {
        try {
            $service->process($this->data, $this->payload);
        } catch (\Throwable $exception) {
            Log::channel('meta_leads')->error('Erro ao processar lead inbound.', [
                'leadgen_id' => $this->data['leadgen_id'] ?? $this->payload['leadgenId'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
