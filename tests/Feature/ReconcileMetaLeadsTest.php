<?php

namespace Tests\Feature;

use App\Jobs\ProcessMetaLeadJob;
use App\Models\Lead;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReconcileMetaLeadsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_queues_only_recent_leads_missing_from_the_backoffice(): void
    {
        Queue::fake();
        config([
            'services.meta.access_token' => 'system-token',
            'services.meta.graph_version' => 'v25.0',
            'services.meta.page_id' => 'page-1',
            'services.meta.lead_reconciliation' => [
                'enabled' => true,
                'form_ids' => ['form-1'],
                'lookback_minutes' => 60,
                'page_size' => 100,
                'max_pages_per_form' => 1,
            ],
        ]);

        Lead::create([
            'leadgen_id' => 'lead-existing',
            'page_id' => 'page-1',
            'form_id' => 'form-1',
            'status' => Lead::STATUS_NEW,
        ]);

        Http::fake([
            'graph.facebook.com/v25.0/form-1/leads*' => Http::response([
                'data' => [
                    ['id' => 'lead-new', 'created_time' => now()->subMinutes(5)->toIso8601String()],
                    ['id' => 'lead-existing', 'created_time' => now()->subMinutes(5)->toIso8601String()],
                    ['id' => 'lead-old', 'created_time' => now()->subHours(2)->toIso8601String()],
                ],
            ]),
        ]);

        $this->artisan('meta:reconcile-leads')
            ->expectsOutputToContain('1 colocadas na fila')
            ->assertExitCode(0);

        Queue::assertPushedOn('meta-leads', ProcessMetaLeadJob::class, function (ProcessMetaLeadJob $job) {
            return $job->value['leadgen_id'] === 'lead-new'
                && $job->value['form_id'] === 'form-1';
        });
        Queue::assertPushed(ProcessMetaLeadJob::class, 1);
    }
}
