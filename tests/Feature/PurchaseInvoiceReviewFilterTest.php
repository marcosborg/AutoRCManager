<?php

namespace Tests\Feature;

use App\Models\{GeneralState, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseInvoiceReviewFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions = ['vehicle_access', 'vehicle_documents_area_access']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Invoice review ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(string $suffix, array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge(array_fill_keys(array_keys(VehicleDocuments::checks()), 1), [
            'license' => 'INVOICEREV'.$suffix,
            'general_state_id' => GeneralState::firstOrCreate(['name' => 'INVOICE REVIEW STOCK'])->id,
        ], $attributes));
    }

    private function file(Vehicle $vehicle, ?string $type, string $collection = 'additional_documents', bool $annulled = false)
    {
        $properties = $type ? ['document_classification' => ['type' => $type]] : [];
        if ($annulled) $properties['document_annulment'] = ['reason' => 'Duplicado'];
        return $vehicle->media()->create(['collection_name' => $collection, 'name' => 'Invoice review proof',
            'file_name' => 'invoice.pdf', 'disk' => 'public', 'size' => 1, 'manipulations' => [],
            'custom_properties' => $properties, 'generated_conversions' => [], 'responsive_images' => []]);
    }

    private function review(array $filters = [])
    {
        return $this->get(route('admin.vehicles.documents.review', array_merge([
            'scope' => 'all', 'license' => 'INVOICEREV', 'reason' => 'purchase_invoice',
        ], $filters)))->assertOk();
    }

    public function test_only_active_identified_purchase_invoices_remove_vehicles_from_this_explicit_filter(): void
    {
        $this->actingAs($this->actor());
        $missing = $this->vehicle('MISSING');
        $expected = [$missing->id];
        foreach ([['ACTIVE','purchase_invoice','additional_documents',false], ['DOCS','purchase_invoice','documents',false],
            ['UNKNOWN',null,'additional_documents',false], ['SIGNED','signed_purchase_form','additional_documents',false],
            ['ANNULLED','purchase_invoice','additional_documents',true], ['PHOTO','purchase_invoice','photos',false]] as [$suffix,$type,$collection,$annulled]) {
            $vehicle = $this->vehicle($suffix);
            $this->file($vehicle, $type, $collection, $annulled);
            if (!in_array($suffix, ['ACTIVE','DOCS'])) $expected[] = $vehicle->id;
        }
        $result = $this->review()->assertSee('Pode existir um ficheiro por identificar ou um documento em papel.')
            ->assertSee('Acompanhar fatura de compra');
        $this->assertEqualsCanonicalizing($expected, $result->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([], VehicleDocuments::pendingChecks($missing));
        $this->assertSame(0, $this->review(['license' => $missing->license, 'reason' => 'any'])->viewData('vehicles')->total());
    }

    public function test_dispensed_invoice_is_excluded_and_review_restores_it_without_altering_records(): void
    {
        $this->actingAs($this->actor());
        $vehicle = $this->vehicle('DISPENSED');
        $vehicle->forceFill(['document_applicability' => ['purchase_invoice' => ['status' => 'not_applicable', 'reason' => 'Conferido']]])->save();
        $before = $vehicle->fresh()->getAttributes();
        $this->assertSame(0, $this->review(['license' => $vehicle->license])->viewData('vehicles')->total());
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
        $vehicle->forceFill(['document_applicability' => ['purchase_invoice' => ['status' => 'review', 'reason' => 'Conferir novamente']]])->save();
        $before = $vehicle->fresh()->getAttributes();
        $this->assertSame(1, $this->review(['license' => $vehicle->license])->viewData('vehicles')->total());
        $this->assertSame($before, $vehicle->fresh()->getAttributes());
        $this->assertSame(0, $vehicle->documentFollowups()->count());
    }

    public function test_scope_company_plate_and_permissions_remain_enforced(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $company = array_key_first(Vehicle::DESTINATION_COMPANIES);
        $stock = $this->vehicle('STOCK', ['destination_company' => $company]);
        $sold = $this->vehicle('SOLD', ['destination_company' => $company, 'sale_date' => '2026-10-01']);
        $unassigned = $this->vehicle('UNASSIGNED');
        $deleted = $this->vehicle('DELETED', ['destination_company' => $company]);
        $deleted->delete();
        $this->assertSame([$stock->id], $this->review(['scope' => 'stock', 'destination_company' => $company])->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$sold->id], $this->review(['license' => $sold->license])->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$unassigned->id], $this->review(['destination_company' => 'unassigned'])->viewData('vehicles')->pluck('id')->all());
        $this->review(['license' => $stock->license])->assertDontSee('Abrir documentos');
        $this->get(route('admin.vehicles.documents.followup', [$stock, 'purchase_invoice']))->assertOk();
        $this->assertSame(0, $stock->documentFollowups()->count());
        $this->actingAs($this->actor(['vehicle_access']));
        $this->get(route('admin.vehicles.documents.review', ['reason' => 'purchase_invoice']))->assertForbidden();
    }
}
