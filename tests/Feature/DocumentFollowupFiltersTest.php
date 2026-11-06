<?php

namespace Tests\Feature;

use App\Models\{GeneralState, Permission, Role, User, Vehicle};
use App\Support\VehicleDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DocumentFollowupFiltersTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Followup filter ')]);
        foreach (['vehicle_access', 'vehicle_documents_area_access'] as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        }
        $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(string $suffix, array $extra = []): Vehicle
    {
        return Vehicle::create(array_merge(array_fill_keys(array_keys(VehicleDocuments::checks()), 1), [
            'license' => 'FILTERDOC'.$suffix,
            'general_state_id' => GeneralState::firstOrCreate(['name' => 'DOCUMENT FILTER STOCK'])->id,
        ], $extra));
    }

    private function followup(Vehicle $vehicle, User $user, string $status, string $date, string $document = 'vehicle_ownership_title')
    {
        return $vehicle->documentFollowups()->create(['document' => $document, 'assigned_to_id' => $user->id,
            'contact' => 'Fornecedor', 'requested_on' => '2026-01-01', 'next_contact_on' => $date,
            'status' => $status, 'version' => 1, 'history' => [], 'note' => 'Conferir documento']);
    }

    private function review(string $reason, array $extra = [])
    {
        return $this->get(route('admin.vehicles.documents.review', array_merge([
            'scope' => 'all', 'license' => 'FILTERDOC', 'reason' => $reason,
        ], $extra)))->assertOk();
    }

    public function test_filters_distinguish_open_due_and_review_without_mutating_followups(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 12:00:00', 'Europe/Lisbon'));
        $user = $this->actor();
        $this->actingAs($user);
        $vehicles = [];
        $followups = [];
        foreach (['OVERDUE' => ['open','2026-11-05'], 'TODAY' => ['open','2026-11-06'],
            'FUTURE' => ['open','2026-11-07'], 'REVIEW' => ['review','2026-11-05'],
            'COMPLETE' => ['completed','2026-11-05'], 'DISPENSED' => ['dispensed','2026-11-05']] as $suffix => [$status,$date]) {
            $vehicles[$suffix] = $this->vehicle($suffix);
            $followups[] = $this->followup($vehicles[$suffix], $user, $status, $date);
        }
        $checkOnly = $this->vehicle('CHECKONLY', ['vehicle_ownership_title' => 0]);
        $before = array_map(fn ($f) => $f->fresh()->getAttributes(), $followups);
        $due = $this->review('due')->assertSee('Em atraso')->assertSee('Hoje');
        $this->assertEqualsCanonicalizing([$vehicles['OVERDUE']->id, $vehicles['TODAY']->id], $due->viewData('vehicles')->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$vehicles['OVERDUE']->id, $vehicles['TODAY']->id, $vehicles['FUTURE']->id], $this->review('followups')->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$vehicles['REVIEW']->id], $this->review('review')->viewData('vehicles')->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$vehicles['OVERDUE']->id, $vehicles['TODAY']->id, $vehicles['FUTURE']->id,
            $vehicles['REVIEW']->id, $checkOnly->id], $this->review('any')->viewData('vehicles')->pluck('id')->all());
        $this->assertSame($before, array_map(fn ($f) => $f->fresh()->getAttributes(), $followups));
    }

    public function test_due_filter_matches_lisbon_date_and_only_displays_matching_followups(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-07-01 23:30:00', 'UTC'));
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = $this->vehicle('MIXED');
        $due = $this->followup($vehicle, $user, 'open', '2026-07-02');
        $this->followup($vehicle, $user, 'open', '2026-07-03', 'purchase_invoice');
        $this->followup($vehicle, $user, 'review', '2026-07-01', 'inspection');
        $response = $this->review('due')->assertSee('Hoje');
        $this->assertSame('2026-07-02', $response->viewData('today'));
        $this->assertSame([$due->id], $response->viewData('vehicles')->first()->documentFollowups->pluck('id')->all());
        $this->assertSame(1, $response->viewData('vehicles')->total());
    }

    public function test_followup_filters_preserve_stock_company_and_plate_constraints(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $company = array_key_first(Vehicle::DESTINATION_COMPANIES);
        $stock = $this->vehicle('STOCK', ['destination_company' => $company]);
        $sold = $this->vehicle('SOLD', ['destination_company' => $company, 'sale_date' => '2026-10-01']);
        $unassigned = $this->vehicle('UNASSIGNED');
        foreach ([$stock, $sold, $unassigned] as $v) $this->followup($v, $user, 'open', '2026-01-01');
        $stockResponse = $this->review('followups', ['scope' => 'stock', 'destination_company' => $company]);
        $this->assertSame([$stock->id], $stockResponse->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$sold->id], $this->review('followups', ['license' => $sold->license])->viewData('vehicles')->pluck('id')->all());
        $this->assertSame([$unassigned->id], $this->review('followups', ['destination_company' => 'unassigned'])->viewData('vehicles')->pluck('id')->all());
    }
}
