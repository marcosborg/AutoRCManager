<?php

namespace Tests\Feature;

use App\Models\{GeneralState, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InspectionReviewFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions = ['vehicle_access', 'vehicle_documents_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('IPO review ')]);
        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        }
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(string $suffix, array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge(array_fill_keys(array_keys(VehicleDocuments::checks()), 1), [
            'license' => 'IPOREVIEW'.$suffix,
            'general_state_id' => GeneralState::firstOrCreate(['name' => 'IPO REVIEW STOCK'])->id,
        ], $attributes));
    }

    private function file(Vehicle $vehicle, ?string $type, string $collection, bool $annulled): void
    {
        $properties = $type ? ['document_classification' => ['type' => $type]] : [];
        if ($annulled) $properties['document_annulment'] = ['reason' => 'Duplicado'];
        $vehicle->media()->create(['collection_name' => $collection, 'name' => 'IPO review proof',
            'file_name' => 'inspection.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [],
            'custom_properties' => $properties, 'generated_conversions' => [], 'responsive_images' => []]);
    }

    private function review(array $filters = [])
    {
        return $this->get(route('admin.vehicles.documents.review', array_merge([
            'scope' => 'all', 'license' => 'IPOREVIEW', 'reason' => 'inspection',
        ], $filters)))->assertOk();
    }

    public function test_only_active_ipo_or_combined_documents_remove_a_vehicle_from_the_explicit_filter(): void
    {
        $this->actingAs($this->actor());
        $missing = $this->vehicle('MISSING');
        $expected = [$missing->id];
        foreach ([
            ['IPO', 'inspection', 'documents', false, false],
            ['COMBINED', 'dua_inspection', 'additional_documents', false, false],
            ['DAVCOLLECTION', 'inspection', 'dav', false, false],
            ['DUA', 'dua', 'documents', false, true],
            ['UNKNOWN', null, 'documents', false, true],
            ['ANNULLED', 'inspection', 'documents', true, true],
            ['COMBINEDANNULLED', 'dua_inspection', 'documents', true, true],
            ['PHOTO', 'inspection', 'photos', false, true],
        ] as [$suffix, $type, $collection, $annulled, $included]) {
            $vehicle = $this->vehicle($suffix);
            $this->file($vehicle, $type, $collection, $annulled);
            if ($included) $expected[] = $vehicle->id;
        }
        $response = $this->review()->assertSee('Esta consulta não confirma a obrigatoriedade nem a validade da inspeção.')
            ->assertSee('Acompanhar inspeção (IPO)');
        $this->assertEqualsCanonicalizing($expected, $response->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([], VehicleDocuments::pendingChecks($missing));
        $this->assertSame(0, $this->review(['license' => $missing->license, 'reason' => 'any'])->viewData('vehicles')->total());
    }

    public function test_not_applicable_is_excluded_and_review_restores_visibility_without_writes(): void
    {
        $this->actingAs($this->actor());
        $vehicle = $this->vehicle('DISPENSED');
        foreach (['not_applicable' => 0, 'review' => 1] as $status => $expected) {
            $vehicle->forceFill(['document_applicability' => ['inspection' => ['status' => $status, 'reason' => 'Conferido']]])->save();
            $before = $vehicle->fresh()->getAttributes();
            $this->assertSame($expected, $this->review(['license' => $vehicle->license])->viewData('vehicles')->total());
            $this->assertSame($before, $vehicle->fresh()->getAttributes());
            $this->assertSame(0, $vehicle->documentFollowups()->count());
        }
    }

    public function test_existing_scope_search_company_and_reader_permissions_are_preserved(): void
    {
        $this->actingAs($this->actor());
        $company = array_key_first(Vehicle::DESTINATION_COMPANIES);
        $stock = $this->vehicle('STOCK', ['destination_company' => $company, 'foreign_license' => 'IPOFOREIGN']);
        $sold = $this->vehicle('SOLD', ['destination_company' => $company, 'sale_date' => '2026-10-01']);
        $unassigned = $this->vehicle('UNASSIGNED');
        $deleted = $this->vehicle('DELETED', ['destination_company' => $company]);
        $deleted->delete();
        $this->assertSame([$stock->id], $this->review(['scope' => 'stock', 'destination_company' => $company])->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$sold->id], $this->review(['license' => $sold->license])->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$stock->id], $this->review(['license' => 'IPOFOREIGN'])->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$unassigned->id], $this->review(['destination_company' => 'unassigned'])->viewData('vehicles')->pluck('id')->all());
        $this->review(['license' => $stock->license])->assertDontSee('Abrir documentos');
        $this->get(route('admin.vehicles.documents.followup', [$stock, 'inspection']))->assertOk();
        $this->assertSame(0, $stock->documentFollowups()->count());
        $this->actingAs($this->actor(['vehicle_access']));
        $this->get(route('admin.vehicles.documents.review', ['reason' => 'inspection']))->assertForbidden();
    }
}
