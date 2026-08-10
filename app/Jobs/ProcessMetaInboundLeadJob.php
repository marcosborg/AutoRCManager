<?php

namespace App\Jobs;

use App\Services\MetaInboundLeadService;
use App\Models\LeadIngestion;
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

    public function __construct(public array $data, public array $payload, public ?int $ingestionId = null)
    {
        $this->onQueue('meta-leads');
    }

    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function handle(MetaInboundLeadService $service): void
    {
        $ingestion = $this->ingestionId ? LeadIngestion::find($this->ingestionId) : null;

        try {
            if ($ingestion) {
                $ingestion->update(['status' => LeadIngestion::STATUS_PROCESSING, 'last_error' => null]);
            }

            $lead = $service->process($this->data, $this->payload);

            if ($ingestion) {
                $ingestion->update([
                    'lead_id' => $lead->id,
                    'status' => LeadIngestion::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'last_error' => null,
                ]);
            }
        } catch (\Throwable $exception) {
            if ($ingestion) {
                $ingestion->update([
                    'status' => LeadIngestion::STATUS_FAILED,
                    'last_error' => $exception->getMessage(),
                ]);
            }
            Log::channel('meta_leads')->error('Erro ao processar lead inbound.', [
                'leadgen_id' => $this->data['leadgen_id'] ?? $this->payload['leadgenId'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
