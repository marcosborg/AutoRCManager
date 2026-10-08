<?php

namespace Tests\Feature;

use App\Models\ExternalService;
use App\Models\Brand;
use App\Models\GeneralState;
use App\Models\Role;
use App\Models\Vehicle;
use App\Services\VehicleProfitabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExternalServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_external_service_form_uses_only_license_plate_and_an_invoice_file(): void
    {
        $user = Role::where('title', 'Chefe oficina')->firstOrFail()->users()->firstOrFail();
        $vehicle = Vehicle::create([
            'license' => uniqid('EXT-'),
            'brand_id' => Brand::firstOrCreate(['name' => 'External service test brand'])->id,
            'general_state_id' => GeneralState::firstOrCreate(['name' => 'EXTERNAL SERVICE TEST'])->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.external-services.create'));

        $response->assertOk();
        $document = new \DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $xpath = new \DOMXPath($document);
        $form = '//form[@action="'.route('admin.external-services.store').'"]';
        $this->assertSame(1, $xpath->query($form)->length);
        $option = $xpath->query($form.'//select[@name="vehicle_id"]/option[@value="'.$vehicle->id.'"]');
        $this->assertSame(1, $option->length);
        $this->assertSame($vehicle->license, trim($option->item(0)->textContent));
        $this->assertSame(1, $xpath->query($form.'//input[@name="invoice_file" and @type="file"]')->length);
        $this->assertSame(0, $xpath->query($form.'//*[@name="repair_id" or @name="invoice_number"]')->length);

    }

    public function test_workshop_user_can_create_an_external_service_that_counts_as_a_workshop_cost(): void
    {
        Storage::fake('public');

        $user = Role::where('title', 'Chefe oficina')->firstOrFail()->users()->firstOrFail();
        $vehicle = Vehicle::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.external-services.store'), [
                'vehicle_id' => $vehicle->id,
                'description' => 'Reparação externa de jante',
                'priority' => 'normal',
                'status' => 'completed',
                'amount' => 125.50,
                'invoice_file' => UploadedFile::fake()->create('fatura.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.external-services.index'));

        $service = ExternalService::where('vehicle_id', $vehicle->id)
            ->where('description', 'Reparação externa de jante')
            ->firstOrFail();

        $this->assertSame(125.50, $service->amount);
        $this->assertNotNull($service->completed_date);
        $this->assertCount(1, $service->getMedia('invoice_file'));

        $profitability = app(VehicleProfitabilityService::class)->build($vehicle->fresh());
        $this->assertGreaterThanOrEqual(125.50, $profitability['workshop_external_services']);
    }

    public function test_cancelled_external_service_does_not_count_as_a_workshop_cost(): void
    {
        $vehicle = Vehicle::query()->firstOrFail();
        $before = app(VehicleProfitabilityService::class)->build($vehicle)['workshop_external_services'];

        ExternalService::create([
            'vehicle_id' => $vehicle->id,
            'description' => 'Serviço cancelado',
            'priority' => 'normal',
            'status' => 'cancelled',
            'amount' => 999,
        ]);

        $after = app(VehicleProfitabilityService::class)->build($vehicle->fresh())['workshop_external_services'];
        $this->assertSame($before, $after);
    }
}
