<?php

namespace Tests\Feature;

use App\Models\VehicleDocumentFollowup;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PurchaseSignatureAgeAlertTest extends TestCase
{
    private function followup(array $attributes = []): VehicleDocumentFollowup
    {
        return new VehicleDocumentFollowup($attributes + [
            'document' => 'signed_purchase_form', 'status' => 'open',
            'requested_on' => '2026-11-03', 'next_contact_on' => '2026-12-01', 'version' => 4,
        ]);
    }

    public function test_alert_starts_at_three_calendar_days_and_does_not_change_chosen_deadline(): void
    {
        $now = CarbonImmutable::parse('2026-11-06 12:00:00', 'Europe/Lisbon');
        foreach (['2026-11-07' => null, '2026-11-06' => null, '2026-11-05' => null, '2026-11-04' => null, '2026-11-03' => 3, '2026-10-30' => 7] as $requested => $days) {
            $followup = $this->followup(['requested_on' => $requested]);
            $before = $followup->getAttributes();
            $this->assertSame($days, $followup->purchaseSignatureAgeAlertDays($now));
            $this->assertSame($before, $followup->getAttributes());
            $this->assertFalse($followup->exists);
        }
    }

    public function test_only_open_signed_purchase_followups_have_this_alert(): void
    {
        $now = CarbonImmutable::parse('2026-11-06', 'Europe/Lisbon');
        foreach (['completed', 'dispensed', 'review'] as $status) {
            $this->assertNull($this->followup(['status' => $status])->purchaseSignatureAgeAlertDays($now));
        }
        foreach (['signed_sale_declaration', 'dua', 'purchase_invoice', 'auction_pickup_authorization'] as $document) {
            $this->assertNull($this->followup(['document' => $document])->purchaseSignatureAgeAlertDays($now));
        }
        foreach ([null, '', 'invalid', '2026-02-30', '2026-11-03 10:00:00'] as $requested) {
            $this->assertNull($this->followup(['requested_on' => $requested])->purchaseSignatureAgeAlertDays($now));
        }
    }

    public function test_lisbon_calendar_days_are_used_across_timezones_and_daylight_saving(): void
    {
        $followup = $this->followup(['requested_on' => '2026-06-04']);
        $this->assertSame(3, $followup->purchaseSignatureAgeAlertDays(CarbonImmutable::parse('2026-06-06 23:30:00', 'UTC')));
        $this->assertNull($followup->purchaseSignatureAgeAlertDays(CarbonImmutable::parse('2026-06-06 22:30:00', 'UTC')));
        $this->assertSame(3, $this->followup(['requested_on' => '2026-10-23'])
            ->purchaseSignatureAgeAlertDays(CarbonImmutable::parse('2026-10-26 00:00:00', 'Europe/Lisbon')));
    }

    public function test_badge_is_display_only_and_does_not_make_financial_claims(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-06 12:00:00', 'Europe/Lisbon'));
        try {
            $followup = $this->followup();
            $html = view('admin.vehicles.partials.purchase-signature-age', compact('followup'))->render();
            $this->assertStringContainsString('À espera de assinatura há 3 dias', $html);
            $this->assertStringNotContainsString('pago', $html);
            $this->assertStringNotContainsString('Em atraso', $html);
            foreach ([['requested_on' => '2026-11-04'], ['status' => 'completed'], ['document' => 'signed_sale_declaration']] as $attributes) {
                $html = view('admin.vehicles.partials.purchase-signature-age', ['followup' => $this->followup($attributes)])->render();
                $this->assertSame('', trim($html));
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
