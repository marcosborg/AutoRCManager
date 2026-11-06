<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Tests\TestCase;

class VehiclePurchasePrintPresentationTest extends TestCase
{
    private function data(array $changes = []): array
    {
        return array_replace([
            'seller_name' => 'Particular teste', 'seller_nif' => '999999990', 'seller_address' => 'Morada teste',
            'buyer_name' => 'Empresa teste', 'buyer_nif' => '999999990', 'buyer_address' => 'Morada teste',
            'vehicle_license' => '12-AB-34', 'vehicle_description' => 'Viatura teste', 'vin' => null,
            'amount' => '12500.50', 'place' => 'Ovar', 'purchase_date' => '2026-11-06',
        ], $changes);
    }

    private function render(array $data, bool $pdf): string
    {
        $vehicle = new Vehicle(['license' => '12-AB-34']);
        $vehicle->id = 123;
        return view('admin.vehicles.purchase-print', compact('vehicle', 'data', 'pdf'))->render();
    }

    public function test_preview_and_pdf_use_portuguese_long_date_and_leave_optional_chassis_blank(): void
    {
        foreach ([false, true] as $pdf) {
            foreach ([null, '', '   '] as $vin) {
                $html = $this->render($this->data(['vin' => $vin]), $pdf);
                $this->assertStringContainsString('6 de novembro de 2026', $html);
                $this->assertStringNotContainsString('06/11/2026', $html);
                $this->assertStringContainsString('________________________', $html);
                $this->assertStringNotContainsString('Não indicado', $html);
                $this->assertStringContainsString('12-AB-34', $html);
                $this->assertStringContainsString('12.500,50', $html);
                $this->assertStringContainsString('doze mil e quinhentos euros e cinquenta cêntimos', $html);
            }
        }
    }

    public function test_date_locale_is_explicit_and_saved_vehicle_identifiers_are_preserved_and_escaped(): void
    {
        $previous = \Carbon\Carbon::getLocale();
        \Carbon\Carbon::setLocale('en');
        try {
            foreach ([false, true] as $pdf) {
                $data = $this->data(['purchase_date' => '2024-02-29', 'vin' => 'WVW12345678901234', 'vehicle_license' => 'AB-123-CD']);
                $html = $this->render($data, $pdf);
                $this->assertStringContainsString('29 de fevereiro de 2024', $html);
                $this->assertStringContainsString('WVW12345678901234', $html);
                $this->assertStringContainsString('AB-123-CD', $html);
                $this->assertStringNotContainsString('________________________', $html);
                $unsafe = $this->render($this->data(['vin' => '<script>alert(1)</script>']), $pdf);
                $this->assertStringNotContainsString('<script>alert(1)</script>', $unsafe);
                $this->assertStringContainsString('&lt;script&gt;', $unsafe);
            }
        } finally {
            \Carbon\Carbon::setLocale($previous);
        }
    }
}
