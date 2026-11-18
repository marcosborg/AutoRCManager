<?php

namespace Tests\Feature;

use App\Models\{AuditLog, ExternalService, Permission, Role, User, Vehicle};
use App\Services\{VehicleCostReviewService, VehicleProfitabilityService};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalServiceInvoiceReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function vehicle(): Vehicle
    {
        return Vehicle::create(['license' => uniqid('INV-'), 'purchase_price' => 1000]);
    }

    private function service(Vehicle $vehicle, array $attributes = []): ExternalService
    {
        return ExternalService::create(array_merge([
            'vehicle_id' => $vehicle->id, 'description' => 'Serviço para conferência',
            'status' => 'completed', 'amount' => 50,
        ], $attributes));
    }

    private function media(ExternalService $service, string $collection = 'invoice_file'): void
    {
        $service->media()->create([
            'collection_name' => $collection, 'name' => 'Fatura de teste', 'file_name' => 'invoice-review-test.pdf',
            'disk' => 'public', 'size' => 1, 'manipulations' => [], 'custom_properties' => [],
            'generated_conversions' => [], 'responsive_images' => [],
        ]);
    }

    private function rows(Vehicle $vehicle): \Illuminate\Support\Collection
    {
        return collect(app(VehicleCostReviewService::class)->build($vehicle->fresh())['rows'])->keyBy('id');
    }

    public function test_only_invoice_collection_proves_an_attachment_without_changing_payment_or_cost(): void
    {
        $vehicle = $this->vehicle();
        $attached = $this->service($vehicle);
        $missing = $this->service($vehicle);
        $otherCollection = $this->service($vehicle);
        $this->media($attached);
        $this->media($otherCollection, 'other_document');
        $rows = $this->rows($vehicle);

        $this->assertContains('Fatura anexada', $rows['external_service_'.$attached->id]['notes']);
        foreach ([$missing, $otherCollection] as $service) {
            $this->assertContains('Fatura por confirmar', $rows['external_service_'.$service->id]['notes']);
            $this->assertNotContains('Fatura anexada', $rows['external_service_'.$service->id]['notes']);
        }
        foreach ([$attached, $missing, $otherCollection] as $service) {
            $row = $rows['external_service_'.$service->id];
            $this->assertSame('Não identificado', $row['payment_status']);
            $this->assertTrue($row['included']);
            $this->assertSame(50.0, $row['amount']);
        }
        $this->assertSame(1150.0, app(VehicleProfitabilityService::class)->build($vehicle->fresh())['global_cost']);
        $this->assertSame(1150.0, app(VehicleCostReviewService::class)->build($vehicle->fresh())['current_total']);
    }

    public function test_cancelled_service_retains_invoice_evidence_without_requesting_missing_invoice_or_adding_cost(): void
    {
        $vehicle = $this->vehicle();
        $attached = $this->service($vehicle, ['status' => 'cancelled']);
        $missing = $this->service($vehicle, ['status' => 'cancelled']);
        $this->media($attached);
        $rows = $this->rows($vehicle);

        $this->assertContains('Fatura anexada', $rows['external_service_'.$attached->id]['notes']);
        $this->assertContains('Sem fatura anexada — serviço cancelado.', $rows['external_service_'.$missing->id]['notes']);
        foreach ([$attached, $missing] as $service) {
            $row = $rows['external_service_'.$service->id];
            $this->assertNotContains('Fatura por confirmar', $row['notes']);
            $this->assertFalse($row['included']);
            $this->assertSame('Não identificado', $row['payment_status']);
        }
        $this->assertSame(1000.0, app(VehicleCostReviewService::class)->build($vehicle->fresh())['current_total']);
    }

    public function test_attached_invoice_does_not_turn_missing_amount_into_confirmed_zero(): void
    {
        $vehicle = $this->vehicle();
        $service = $this->service($vehicle, ['amount' => null]);
        $this->media($service);
        $row = $this->rows($vehicle)['external_service_'.$service->id];

        $this->assertContains('Fatura anexada', $row['notes']);
        $this->assertTrue($row['amount_missing']);
        $this->assertSame('Por confirmar', $row['value_status']);
        $this->assertSame('Não identificado', $row['payment_status']);
        $this->assertSame(1000.0, app(VehicleCostReviewService::class)->build($vehicle->fresh())['current_total']);
    }

    public function test_repeated_reads_preserve_media_services_financial_values_and_audit_history(): void
    {
        $vehicle = $this->vehicle();
        $service = $this->service($vehicle);
        $this->media($service);
        $snapshot = fn () => [
            DB::table('vehicles')->where('id', $vehicle->id)->first(),
            DB::table('external_services')->where('id', $service->id)->first(),
            $service->fresh()->media->map->getAttributes()->all(),
            AuditLog::count(),
            app(VehicleProfitabilityService::class)->build($vehicle->fresh()),
        ];
        $before = $snapshot();
        $first = app(VehicleCostReviewService::class)->build($vehicle->fresh());
        $second = app(VehicleCostReviewService::class)->build($vehicle->fresh());

        $this->assertSame($first, $second);
        $this->assertEquals($before, $snapshot());
        $this->assertTrue($first['reconciles']);
    }

    public function test_invoice_note_renders_but_service_source_link_requires_its_existing_permission(): void
    {
        $vehicle = $this->vehicle();
        $service = $this->service($vehicle);
        $this->media($service);
        $url = route('admin.external-services.edit', $service);
        // Keep the ability defined when removed from Adm between requests.
        $otherRole = Role::create(['title' => 'Invoice review permission holder']);
        $otherRole->permissions()->attach(Permission::firstOrCreate(['title' => 'external_service_edit'])->id);

        $this->actingAs($this->user('Adm', true))
            ->get(route('admin.vehicles.edit', $vehicle))->assertOk()
            ->assertSee('Fatura anexada')->assertSee($url, false);
        $this->actingAs($this->user('Adm', false))
            ->get(route('admin.vehicles.edit', $vehicle))->assertOk()
            ->assertSee('Fatura anexada')->assertDontSee($url, false);
    }

    public function test_workshop_roles_do_not_gain_access_to_financial_invoice_review(): void
    {
        $vehicle = $this->vehicle();
        $service = $this->service($vehicle);
        $this->media($service);
        foreach (['Aux. oficina', 'Chefe oficina'] as $title) {
            $this->actingAs($this->user($title, true))
                ->get(route('admin.vehicles.edit', $vehicle))->assertOk()
                ->assertDontSee('Conferência dos custos')->assertDontSee('Fatura anexada');
        }
    }

    private function user(string $title, bool $canEditService): User
    {
        $role = Role::firstOrCreate(['title' => $title]);
        $abilities = ['vehicle_edit', 'financial_sensitive_access'];
        if ($canEditService) {
            $abilities[] = 'external_service_edit';
        }
        $role->permissions()->sync(collect($abilities)->map(fn ($ability) => Permission::firstOrCreate(['title' => $ability])->id));
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user;
    }
}
