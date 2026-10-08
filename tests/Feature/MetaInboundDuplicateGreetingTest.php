<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\AiLeadAssistantService;
use App\Services\MetaInboundLeadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class MetaInboundDuplicateGreetingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_duplicate_inbound_lead_updates_data_without_queuing_another_greeting(): void
    {
        Http::preventStrayRequests();
        Notification::fake();
        Queue::fake();

        $lead = Lead::create([
            'leadgen_id' => 'reconcile-'.Str::uuid(),
            'page_id' => 'test-page',
            'form_id' => 'test-form',
            'status' => Lead::STATUS_NEW,
        ]);
        $assistant = Mockery::mock(AiLeadAssistantService::class);
        $assistant->shouldReceive('syncFromMetaLead')->once()->withArgs(
            fn (Lead $updated, bool $queueGreeting) => $updated->id === $lead->id
                && $updated->full_name === 'Cliente de teste'
                && $queueGreeting === false
        )->andReturnNull();
        $this->app->instance(AiLeadAssistantService::class, $assistant);

        $result = app(MetaInboundLeadService::class)->process([
            'leadgen_id' => $lead->leadgen_id,
            'full_name' => 'Cliente de teste',
        ], []);

        $this->assertSame($lead->id, $result->id);
        $this->assertSame('Cliente de teste', $result->full_name);
        $this->assertSame(1, Lead::where('leadgen_id', $lead->leadgen_id)->count());
        Queue::assertNothingPushed();
        Notification::assertNothingSent();
    }
}
