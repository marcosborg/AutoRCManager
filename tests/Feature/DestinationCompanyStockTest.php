<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DestinationCompanyStockTest extends TestCase
{
    use DatabaseTransactions;

    public function test_company_counts_add_up_and_links_filter_the_same_stock(): void
    {
        $admin = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $expected = [];
        foreach (array_keys(Vehicle::DESTINATION_COMPANIES) as $code) {
            $expected[$code] = [Vehicle::create(['model' => 'DASHCOMP STOCK', 'destination_company' => $code])->id];
            Vehicle::create(['model' => 'DASHCOMP SOLD DATE', 'destination_company' => $code, 'sale_date' => now()->toDateString()]);
            Vehicle::create(['model' => 'DASHCOMP SOLD STATE', 'destination_company' => $code, 'general_state_id' => GeneralState::firstOrCreate(['name' => 'VENDIDO STAND'])->id]);
            Vehicle::create(['model' => 'DASHCOMP DELETED', 'destination_company' => $code])->delete();
        }
        $expected['unassigned'] = [
            Vehicle::create(['model' => 'DASHCOMP LEGACY', 'our_registration' => 'Freerent'])->id,
            Vehicle::create(['model' => 'DASHCOMP UNKNOWN', 'destination_company' => 'old_unknown_code'])->id,
        ];
        $dashboard = $this->actingAs($admin)->get(route('admin.home'))->assertOk();
        $business = $dashboard->viewData('business');
        $this->assertSame($business['stock_count'], array_sum($business['stock_by_company']));
        foreach ($expected as $code => $ids) {
            $dashboard->assertSee('destination_company='.$code);
            $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')->getJson(route('admin.vehicles.index', [
                'dashboard_filter' => 'stock', 'destination_company' => $code,
                'draw' => 1, 'start' => 0, 'length' => 100, 'search' => ['value' => 'DASHCOMP'],
            ]))->assertOk()->assertJsonPath('recordsTotal', $business['stock_by_company'][$code])
                ->assertJsonPath('recordsFiltered', count($ids));
            $this->assertEqualsCanonicalizing($ids, collect($response->json('data'))->pluck('id')->all());
        }
    }

    public function test_filter_rejects_unknown_values_and_does_not_grant_access(): void
    {
        $admin = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $this->actingAs($admin)->getJson(route('admin.vehicles.index', ['destination_company' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('destination_company');
        $this->actingAs(User::factory()->create())->get(route('admin.vehicles.index', ['destination_company' => 'freerent']))->assertForbidden();
    }
}
