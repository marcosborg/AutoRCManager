<?php

namespace Tests\Unit;

use App\Jobs\{ProcessMetaInboundLeadJob, ProcessMetaLeadJob};
use PHPUnit\Framework\TestCase;

class MetaLeadQueueTimeoutContractTest extends TestCase
{
    public function test_meta_job_timeouts_leave_margin_before_database_queue_reservation_expires(): void
    {
        $configuration = require __DIR__.'/../../config/queue.php';
        $retryAfter = $configuration['connections']['database']['retry_after'];
        foreach ([new ProcessMetaLeadJob(['leadgen_id' => 'contract-test']),
            new ProcessMetaInboundLeadJob(['leadgen_id' => 'contract-test'], ['original' => true], 123)] as $job) {
            $this->assertSame(75, $job->timeout);
            $this->assertGreaterThan($job->timeout, $retryAfter);
            $this->assertGreaterThan(20, $job->timeout);
            $this->assertSame(3, $job->tries);
            $this->assertSame([10, 60, 180], $job->backoff());
            $this->assertSame('meta-leads', $job->queue);
            $copy = unserialize(serialize($job));
            $this->assertSame($job->timeout, $copy->timeout);
        }
    }

    public function test_timeout_change_preserves_ingestion_and_provider_payloads(): void
    {
        $value = ['leadgen_id' => 'contract-test', 'recovered' => true];
        $this->assertSame($value, (new ProcessMetaLeadJob($value))->value);
        $inbound = new ProcessMetaInboundLeadJob($value, ['original' => true], 123);
        $copy = unserialize(serialize($inbound));
        $this->assertSame($value, $copy->data);
        $this->assertSame(['original' => true], $copy->payload);
        $this->assertSame(123, $copy->ingestionId);
    }
}
