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

    public function __construct(public array $value)
    {
        $this->onQueue('meta-leads');
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
