<?php

namespace Tests\Feature;

use App\Models\{GeneralState, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TransportDocumentReviewFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(bool $allowed = true): User
    {
        $role = Role::create(['title' => uniqid('Transport review ')]);
        foreach ($allowed ? ['vehicle_access', 'vehicle_documents_area_access', 'vehicle_edit'] : ['vehicle_access'] as $ability) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $ability])->id);
        }
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function vehicle(string $prefix, array $attributes = []): Vehicle
    {
        return Vehicle::create($attributes + ['license' => $prefix.uniqid(),
            'general_state_id' => GeneralState::firstOrCreate(['name' => 'Transport review stock'])->id]);
    }

    private function file(Vehicle $vehicle, string $type, bool $annulled = false, string $collection = 'additional_documents')
    {
        return $vehicle->media()->create(['collection_name' => $collection, 'name' => 'Proof',
            'file_name' => 'proof.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [],
            'custom_properties' => ['document_classification' => ['type' => $type]] + ($annulled ? ['document_annulment' => ['reason' => 'Duplicado']] : []),
            'generated_conversions' => [], 'responsive_images' => []]);
    }

    public function test_both_filters_require_the_exact_active_document_and_are_read_only(): void
    {
        $this->actingAs($this->actor());
        foreach (['transport_guide', 'auction_pickup_authorization'] as $type) {
            $prefix = uniqid('TRREVIEW');
            $missing = $this->vehicle($prefix);
            $before = $missing->fresh()->getAttributes();
            $expected = [$missing->id];
            foreach (['active', 'wrong', 'annulled', 'photo'] as $case) {
                $vehicle = $this->vehicle($prefix);
                $this->file($vehicle, $case === 'wrong' ? 'purchase_invoice' : $type, $case === 'annulled', $case === 'photo' ? 'photos' : 'documents');
                if ($case !== 'active') $expected[] = $vehicle->id;
            }
            $dispensed = $this->vehicle($prefix);
            $dispensed->forceFill(['document_applicability' => [$type => ['status' => 'not_applicable']]])->save();
            $deleted = $this->vehicle($prefix);
            $deleted->delete();
            $params = ['reason' => $type, 'scope' => 'all', 'license' => $prefix];
            $response = $this->get(route('admin.vehicles.documents.review', $params))->assertOk()
                ->assertSee('Esta consulta não confirma a obrigatoriedade do documento')
                ->assertSee(route('admin.vehicles.documents.followup', [$missing, $type]), false);
            $this->assertEqualsCanonicalizing($expected, $response->viewData('vehicles')->pluck('id')->all());
            $this->assertSame($before, $missing->fresh()->getAttributes());
            $this->assertSame(0, $missing->documentFollowups()->count());
            $csv = $this->get(route('admin.vehicles.documents.review', $params + ['export' => 'csv']))->assertOk()->streamedContent();
            $this->assertCount(5, explode("\n", trim($csv)));
            // Receiving an identified document removes the pending item on the next consultation.
            $this->file($missing, $type);
            $this->assertFalse($this->get(route('admin.vehicles.documents.review', $params))->viewData('vehicles')->pluck('id')->contains($missing->id));
        }
    }

    public function test_stock_company_and_access_are_respected(): void
    {
        $this->actingAs($this->actor());
        $prefix = uniqid('TRSCOPE');
        $company = array_key_first(Vehicle::DESTINATION_COMPANIES);
        $stock = $this->vehicle($prefix, ['destination_company' => $company]);
        $this->vehicle($prefix, ['destination_company' => $company, 'sale_date' => '2026-11-01']);
        $this->vehicle($prefix);
        foreach (['transport_guide', 'auction_pickup_authorization'] as $type) {
            $params = ['reason' => $type, 'scope' => 'stock', 'license' => $prefix, 'destination_company' => $company];
            $response = $this->get(route('admin.vehicles.documents.review', $params))->assertOk();
            $this->assertSame([$stock->id], $response->viewData('vehicles')->pluck('id')->all());
        }
        $this->actingAs($this->actor(false))->get(route('admin.vehicles.documents.review', ['reason' => 'transport_guide']))->assertForbidden();
    }
}
