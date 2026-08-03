<?php

namespace App\Jobs;

use App\Services\WhatsappWebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWhatsappWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [5, 30, 120, 300];

    public function __construct(public array $payload)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(WhatsappWebhookProcessor $processor): void
    {
        $processor->process($this->payload);
    }
}
