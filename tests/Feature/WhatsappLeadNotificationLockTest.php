<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappLeadNotification;
use App\Models\{Lead, LeadWhatsappNotification, User};
use App\Services\WhatsappCloudApi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WhatsappLeadNotificationLockTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['whatsapp.transport' => 'email', 'whatsapp.lead_notifications_enabled' => true,
            'whatsapp.templates.seller_lead' => ['name' => 'test', 'language' => 'pt_PT']]);
    }

    private function notification(): LeadWhatsappNotification
    {
        $seller = User::factory()->create();
        $lead = Lead::create(['leadgen_id' => uniqid('lock-test-'), 'page_id' => 'test-page', 'form_id' => 'test-form',
            'assigned_user_id' => $seller->id, 'seller_notification_status' => 'pending']);
        return LeadWhatsappNotification::create(['lead_id' => $lead->id, 'user_id' => $seller->id,
            'phone' => '351912000001', 'message' => 'Teste', 'status' => LeadWhatsappNotification::STATUS_PENDING]);
    }

    public function test_reentrant_duplicate_worker_cannot_send_a_second_message(): void
    {
        $notification = $this->notification();
        $api = \Mockery::mock(WhatsappCloudApi::class);
        $api->shouldReceive('sendTemplate')->once()->andReturnUsing(function () use ($notification, $api) {
            $queued = \Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
            $queued->shouldReceive('release')->once()->with(15);
            (new SendWhatsappLeadNotification($notification->id))->setJob($queued)->handle($api);
            $this->assertSame('pending', $notification->fresh()->status);
            $this->assertEquals(1, $notification->fresh()->attempts);
            return ['messages' => [['id' => uniqid('wamid.lock.')]]];
        });
        $job = new SendWhatsappLeadNotification($notification->id);
        $job->handle($api);
        $job->handle($api);
        $this->assertSame('sent', $notification->fresh()->status);
        $this->assertEquals(1, $notification->fresh()->attempts);
        Mail::assertNothingSent();
    }

    public function test_abandoned_lock_releases_queue_job_until_expiry_then_sends_once(): void
    {
        $notification = $this->notification();
        $lock = Cache::store('file')->lock('whatsapp-lead-notification:'.$notification->id, 180);
        $this->assertTrue($lock->get());
        $api = \Mockery::mock(WhatsappCloudApi::class);
        $api->shouldReceive('sendTemplate')->once()->andReturn(['messages' => [['id' => uniqid('wamid.expired.')]]]);
        $queueJob = \Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $queueJob->shouldReceive('release')->once()->with(15);
        $job = new SendWhatsappLeadNotification($notification->id);
        $this->assertSame(0, $job->tries);
        $this->assertSame(4, $job->maxExceptions);
        try {
            $job->setJob($queueJob)->handle($api);
            $this->assertSame('pending', $notification->fresh()->status);
            $this->assertEquals(0, $notification->fresh()->attempts);
            $this->travel(181)->seconds();
            $job->handle($api);
            $job->handle($api);
            $this->assertSame('sent', $notification->fresh()->status);
            $this->assertEquals(1, $notification->fresh()->attempts);
            Mail::assertNothingSent();
        } finally {
            $this->travelBack();
            $lock->release();
        }
    }

    public function test_exception_releases_lock_for_the_original_job_retry(): void
    {
        $notification = $this->notification();
        $api = \Mockery::mock(WhatsappCloudApi::class);
        $api->shouldReceive('sendTemplate')->once()->ordered()->andThrow(new \RuntimeException('Temporary unavailable'));
        $api->shouldReceive('sendTemplate')->once()->ordered()->andReturn(['messages' => [['id' => uniqid('wamid.retry.')]]]);
        $job = new SendWhatsappLeadNotification($notification->id);
        try {
            $job->handle($api);
            $this->fail('The temporary failure must remain retryable.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Temporary unavailable', $exception->getMessage());
        }
        $this->assertSame('pending', $notification->fresh()->status);
        $job->handle($api);
        $this->assertSame('sent', $notification->fresh()->status);
        $this->assertEquals(2, $notification->fresh()->attempts);
        Mail::assertNothingSent();
    }
}
