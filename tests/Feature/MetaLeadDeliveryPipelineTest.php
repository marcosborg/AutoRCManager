<?php

namespace Tests\Feature;

use App\Jobs\ProcessMetaInboundLeadJob;
use App\Models\Lead;
use App\Models\LeadIngestion;
use App\Models\LeadWhatsappNotification;
use App\Models\Role;
use App\Models\User;
use App\Services\LeadWhatsappNotificationService;
use App\Services\MetaInboundLeadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaLeadDeliveryPipelineTest extends TestCase
{
    use DatabaseTransactions;

    public function test_duplicate_inbound_delivery_creates_one_durable_ingestion_and_one_job(): void
    {
        Queue::fake();
        config(['services.meta.inbound_token' => 'inbound-token']);

        $payload = ['leadgen_id' => 'meta-once-1', 'created_time' => now()->subDay()->toIso8601String()];
        $headers = ['Authorization' => 'Bearer inbound-token'];

        $this->postJson('/api/meta/leads/inbound', $payload, $headers)->assertAccepted();
        $this->postJson('/api/meta/leads/inbound', $payload, $headers)->assertAccepted();

        $this->assertSame(1, LeadIngestion::where('source', 'meta_make')->where('external_id', 'meta-once-1')->count());
        Queue::assertPushed(ProcessMetaInboundLeadJob::class, 1);
    }

    public function test_recovered_notifications_are_spaced_one_minute_per_seller(): void
    {
        Carbon::setTestNow('2026-08-10 18:00:00');
        config(['ai_assistant.lead_delivery_channel' => 'whatsapp']);
        $seller = $this->seller();

        $service = app(LeadWhatsappNotificationService::class);
        $notifications = collect(range(1, 3))->map(function (int $index) use ($seller, $service) {
            $lead = Lead::create([
                'leadgen_id' => 'recovered-' . $index,
                'page_id' => 'page',
                'form_id' => 'form',
                'assigned_user_id' => $seller->id,
                'status' => Lead::STATUS_NEW,
            ]);

            return $service->queueForLead($lead, $seller, true);
        });

        $this->assertSame('2026-08-10 18:00:00', $notifications[0]->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-10 18:01:00', $notifications[1]->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-10 18:02:00', $notifications[2]->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame(3, LeadWhatsappNotification::where('user_id', $seller->id)->count());
        Carbon::setTestNow();
    }

    public function test_recovered_notifications_stay_spaced_with_real_clock_microseconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 18:00:00.750000'));
        config(['ai_assistant.lead_delivery_channel' => 'whatsapp']);
        $seller = $this->seller();
        $service = app(LeadWhatsappNotificationService::class);

        $first = Lead::create(['leadgen_id' => 'microsecond-1', 'page_id' => 'page', 'form_id' => 'form', 'assigned_user_id' => $seller->id, 'status' => Lead::STATUS_NEW]);
        $second = Lead::create(['leadgen_id' => 'microsecond-2', 'page_id' => 'page', 'form_id' => 'form', 'assigned_user_id' => $seller->id, 'status' => Lead::STATUS_NEW]);

        $firstNotification = $service->queueForLead($first, $seller, true);
        $secondNotification = $service->queueForLead($second, $seller, true);

        $this->assertSame('2026-08-10 18:00:00', $firstNotification->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-10 18:01:00', $secondNotification->scheduled_for->format('Y-m-d H:i:s'));
        Carbon::setTestNow();
    }

    public function test_delayed_meta_lead_is_queued_instead_of_being_silenced(): void
    {
        config(['ai_assistant.lead_delivery_channel' => 'whatsapp']);
        $seller = $this->seller();

        app(MetaInboundLeadService::class)->process([
            'leadgen_id' => 'delayed-meta-lead',
            'page_id' => 'page',
            'form_id' => 'form',
            'created_time' => now()->subDay()->toIso8601String(),
            'full_name' => 'Lead Atrasada',
        ], []);

        $lead = Lead::where('leadgen_id', 'delayed-meta-lead')->firstOrFail();
        $this->assertNotNull($lead->assigned_user_id);
        $this->assertDatabaseHas('lead_whatsapp_notifications', [
            'lead_id' => $lead->id,
            'user_id' => $lead->assigned_user_id,
            'status' => LeadWhatsappNotification::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'seller_notification_status' => 'pending',
            'seller_notified_user_id' => null,
            'seller_notified_at' => null,
        ]);
    }

    private function seller(): User
    {
        $role = Role::firstOrCreate(['title' => 'Stand']);
        $seller = User::factory()->create(['mobile_phone' => '912000001']);
        $seller->roles()->syncWithoutDetaching([$role->id]);

        return $seller;
    }
}
