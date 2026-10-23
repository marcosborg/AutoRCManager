<?php

namespace Tests\Feature;

use App\Models\{GeneralState, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleDocumentReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $permissions = ['vehicle_access', 'vehicle_documents_area_access', 'vehicle_edit']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Document review ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge(array_fill_keys(array_keys(VehicleDocuments::checks()), 1), [
            'license' => uniqid('REV'), 'model' => 'Review test',
            'general_state_id' => GeneralState::firstOrCreate(['name' => 'DOCUMENT REVIEW STOCK'])->id,
        ], $attributes));
    }

    private function file(Vehicle $vehicle, string $collection, array $properties = [])
    {
        return $vehicle->media()->create(['collection_name' => $collection, 'name' => 'Review document',
            'file_name' => 'dua-inspection.pdf', 'disk' => 'public', 'size' => 1,
            'manipulations' => [], 'custom_properties' => $properties, 'generated_conversions' => [], 'responsive_images' => []]);
    }

    private function review(array $filters = [])
    {
        return $this->get(route('admin.vehicles.documents.review', array_merge(['scope' => 'all'], $filters)))->assertOk();
    }

    public function test_receipt_flags_and_unidentified_files_are_separate_and_never_infer_missing_requirements(): void
    {
        $this->actingAs($this->user());
        $paper = $this->vehicle(['license' => 'REVPAPER']);
        $unchecked = $this->vehicle(['license' => 'REVCHECK', 'vehicle_ownership_title' => 0]);
        $this->file($unchecked, 'documents', ['document_classification' => ['type' => 'dua']]);
        $unknown = $this->vehicle(['license' => 'REVUNKNOWN']);
        $this->file($unknown, 'additional_documents');
        $r = $this->review(['license' => 'REV']);
        $r->assertDontSee('data-vehicle-id="'.$paper->id.'"', false)
            ->assertSee('data-vehicle-id="'.$unchecked->id.'"', false)->assertSee('data-vehicle-id="'.$unknown->id.'"', false)
            ->assertSee('Nenhuma — confirmações preenchidas na ficha')->assertSee('não significa que o documento seja obrigatório');
        $this->review(['license' => 'REV', 'reason' => 'checks'])->assertSee($unchecked->license)->assertDontSee($unknown->license);
        $this->review(['license' => 'REV', 'reason' => 'unidentified'])->assertSee($unknown->license)->assertDontSee($unchecked->license);
    }

    public function test_query_matches_existing_classification_rules_including_legacy_dav_invalid_and_combined_types(): void
    {
        $this->actingAs($this->user());
        foreach ([['dav', []], ['dav', ['document_classification' => null]], ['dav', ['document_classification' => ['type' => 'unknown']]],
            ['documents', []], ['documents', ['document_classification' => ['type' => 'dua_inspection']]],
            ['additional_documents', ['document_classification' => ['type' => 'other']]],
            ['documents', ['document_classification' => ['type' => 'invented']]],
            ['documents', ['document_classification' => 'invalid']], ['documents', ['document_classification' => ['type' => 12]]],
            ['photos', []]] as [$collection, $properties]) {
            $v = $this->vehicle(); $file = $this->file($v, $collection, $properties);
            $expected = in_array($collection, VehicleDocuments::COLLECTIONS) && VehicleDocuments::type($file) === 'unknown';
            $rows = $this->review(['license' => $v->license, 'reason' => 'unidentified'])->viewData('vehicles');
            $this->assertSame($expected ? 1 : 0, $rows->total(), json_encode([$collection, $properties]));
            if ($expected) $this->assertSame(1, $rows->first()->unidentified_count);
        }
    }

    public function test_stock_company_and_normalized_plate_filters_combine_and_deleted_vehicles_are_excluded(): void
    {
        $this->actingAs($this->user());
        $company = array_key_first(Vehicle::DESTINATION_COMPANIES);
        $stock = $this->vehicle(['license' => 'XY12AB', 'vehicle_ownership_title' => null, 'destination_company' => $company]);
        $sold = $this->vehicle(['license' => 'XY12AC', 'vehicle_ownership_title' => 0, 'sale_date' => '2026-10-15']);
        $soldState = $this->vehicle(['license' => 'XY12AD', 'vehicle_ownership_title' => 0, 'general_state_id' => GeneralState::firstOrCreate(['name' => 'VENDIDO STAND'])->id]);
        $deleted = $this->vehicle(['license' => 'XY12AE', 'vehicle_ownership_title' => 0]); $deleted->delete();
        $this->review(['scope' => 'stock', 'license' => 'xy 12', 'destination_company' => $company])->assertSee($stock->license)->assertDontSee($sold->license)->assertDontSee($soldState->license);
        $this->review(['scope' => 'all', 'license' => 'XY12'])->assertSee($stock->license)->assertSee($sold->license)->assertSee($soldState->license)->assertDontSee($deleted->license);
        $this->review(['license' => 'XY12', 'destination_company' => 'unassigned'])->assertDontSee($stock->license)->assertSee($sold->license);
        $foreign = $this->vehicle(['license' => null, 'foreign_license' => 'FOREIGN-03', 'vehicle_ownership_title' => 0]);
        $this->review(['license' => 'foreign03'])->assertSee('data-vehicle-id="'.$foreign->id.'"', false);
    }

    public function test_reclassification_updates_membership_and_counts_without_changing_receipt_flags(): void
    {
        $this->actingAs($this->user()); $v = $this->vehicle(); $f = $this->file($v, 'documents');
        $url = route('admin.vehicles.documents.type', [$v, $f->id]);
        $this->review(['license' => $v->license])->assertSee($v->license);
        $this->patchJson($url, ['type' => 'dua', 'revision' => VehicleDocuments::revision($f)])->assertOk();
        $this->assertSame(0, $this->review(['license' => $v->license])->viewData('vehicles')->total());
        $this->patchJson($url, ['reason' => 'Tipo incorreto', 'type' => 'unknown', 'revision' => VehicleDocuments::revision($f->fresh())])->assertOk();
        $this->assertSame(1, $this->review(['license' => $v->license])->viewData('vehicles')->total());
        $this->assertSame([], VehicleDocuments::pendingChecks($v->fresh()));
    }

    public function test_pagination_preserves_filters_without_duplicate_vehicles(): void
    {
        $this->actingAs($this->user());
        for ($i=0; $i<27; $i++) {
            $v=$this->vehicle(['license' => 'PAGEREV'.$i]);
            $this->file($v, 'documents'); $this->file($v, 'additional_documents');
        }
        $r=$this->review(['license' => 'PAGEREV', 'reason' => 'unidentified']);
        $this->assertSame(27, $r->viewData('vehicles')->total()); $this->assertCount(25, $r->viewData('vehicles'));
        $r->assertSee('license=PAGEREV', false)->assertSee('reason=unidentified', false);
        $page2=$this->review(['license' => 'PAGEREV', 'reason' => 'unidentified', 'page' => 2]);
        $this->assertCount(2, $page2->viewData('vehicles'));
        $this->assertEmpty(array_intersect($r->viewData('vehicles')->pluck('id')->all(), $page2->viewData('vehicles')->pluck('id')->all()));
    }

    public function test_authorized_api_is_read_only_and_returns_the_same_filtered_list(): void
    {
        $v = $this->vehicle(['license' => 'REVIEWAPI', 'vehicle_ownership_title' => 0]);
        $file = $this->file($v, 'documents');
        $before = $v->fresh()->getAttributes();
        $fileBefore = $file->fresh()->getAttributes();
        $user = $this->user();
        $user->roles()->attach(Role::firstOrCreate(['title' => 'Admin']));
        $token = $user->createToken('integration:document-review-test')->plainTextToken;
        for ($i=0; $i<2; $i++) {
            $this->get('/api/v1/backoffice/vehicles/document-review?scope=all&license=REVIEWAPI', ['Authorization' => 'Bearer '.$token])
                ->assertOk()->assertJsonPath('vehicles.data.0.license', 'REVIEWAPI')
                ->assertJsonPath('vehicles.data.0.unidentified_count', 1);
        }
        $this->assertSame($before, $v->fresh()->getAttributes());
        $this->assertSame($fileBefore, $file->fresh()->getAttributes());
    }

    public function test_permissions_and_filters_are_enforced_on_web_and_backoffice_api(): void
    {
        $api='/api/v1/backoffice/vehicles/document-review';
        $this->getJson($api)->assertUnauthorized();
        foreach ([[], ['vehicle_access'], ['vehicle_documents_area_access']] as $permissions) {
            $this->actingAs($this->user($permissions))->get(route('admin.vehicles.documents.review'))->assertForbidden();
            $this->getJson($api)->assertForbidden();
        }
        $this->actingAs($this->user(['vehicle_access', 'vehicle_documents_area_access']));
        $v=$this->vehicle(['vehicle_ownership_title'=>0]);
        $this->review(['license'=>$v->license])->assertDontSee('Abrir documentos');
        foreach (['scope'=>'invalid', 'reason'=>'invalid', 'destination_company'=>'invalid'] as $key=>$value) {
            $this->getJson(route('admin.vehicles.documents.review', [$key=>$value]))->assertUnprocessable();
        }
        $this->get(route('admin.vehicles.documents.review'))->assertOk()->assertViewHas('scope', 'stock')->assertSee('Stock segue o critério atual');
        $this->actingAs($this->user())->review(['license'=>$v->license])->assertSee('#vehicle-documents', false);
    }
}
