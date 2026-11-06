<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Tests\TestCase;

class VehiclePurchaseAmountWordsTest extends TestCase
{
    public function test_purchase_preview_and_pdf_template_include_the_same_amount_in_words(): void
    {
        $vehicle = new Vehicle(['license' => 'TEST-01']);
        $vehicle->id = 123;
        $data = [
            'seller_name' => 'Particular teste', 'seller_nif' => '999999990', 'seller_address' => 'Morada de teste',
            'buyer_name' => 'Empresa teste', 'buyer_nif' => '999999990', 'buyer_address' => 'Morada de teste',
            'vehicle_license' => 'TEST-01', 'vehicle_description' => 'Viatura de teste', 'vin' => null,
            'amount' => '12500.50', 'place' => 'Ovar', 'purchase_date' => '2026-11-06',
        ];
        foreach ([false, true] as $pdf) {
            $html = view('admin.vehicles.purchase-print', compact('vehicle', 'data', 'pdf'))->render();
            $this->assertStringContainsString('12.500,50', $html);
            $this->assertStringContainsString('doze mil e quinhentos euros e cinquenta cêntimos', $html);
            $this->assertStringNotContainsString('Modelo para revisão e validação', $html);
        }
    }
}
