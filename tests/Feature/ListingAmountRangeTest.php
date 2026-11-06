<?php
namespace Tests\Feature;

use App\Models\{Vehicle, User, Role, Permission};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ListingAmountRangeTest extends TestCase
{
    use DatabaseTransactions;

    private function login(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Listing ')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'vehicle_access'])->id);
        $user->roles()->attach($role);
        $this->actingAs($user);
    }

    public function test_range_is_inclusive_and_preserved_across_pages(): void
    {
        $this->login();
        $prefix = uniqid('RANGE');
        foreach ([0, 10.25, 20.50, 30, null] as $pvp) Vehicle::create(['license' => $prefix, 'pvp' => $pvp]);
        foreach (['admin.vehicles.index', 'admin.sales.index'] as $route) {
            $this->app->forgetInstance('datatables.request');
            $params = ['amount_min' => '10,25', 'amount_max' => '20,50', 'search' => ['value' => $prefix],
                'columns' => [['data' => 'license', 'name' => 'license', 'searchable' => 'true']], 'start' => 0, 'length' => 1];
            $first = $this->getJson(route($route, $params), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
            $first->assertJsonPath('recordsFiltered', 2)->assertJsonCount(1, 'data');
            $this->app->forgetInstance('datatables.request');
            $next = $this->getJson(route($route, array_replace($params, ['start' => 1])), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
            $this->assertNotEquals($first->json('data.0.id'), $next->json('data.0.id'));
        }
    }

    public function test_invalid_ranges_are_rejected(): void
    {
        $this->login();
        foreach (['admin.vehicles.index', 'admin.sales.index'] as $route) {
            foreach ([['amount_min' => -1], ['amount_max' => 'invalid'], ['amount_min' => 20, 'amount_max' => 10]] as $params) {
                $this->getJson(route($route, $params))->assertUnprocessable();
            }
        }
    }

    public function test_legacy_text_prices_are_compared_as_amounts_not_alphabetically(): void
    {
        $this->login();
        $prefix = uniqid('LEGACY');
        foreach (['2000', '1305', '1800', '17.990', '10000', '20000', '20001', '18000,50', 'Sob consulta'] as $pvp) {
            Vehicle::create(['license' => $prefix, 'pvp' => $pvp]);
        }
        $response = $this->getJson(route('admin.vehicles.index', ['amount_min' => 10000, 'amount_max' => 20000,
            'search' => ['value' => $prefix]]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $response->assertJsonPath('recordsFiltered', 4);
        $this->assertEqualsCanonicalizing(['17.990', '10000', '20000', '18000,50'], array_column($response->json('data'), 'pvp'));
    }

    public function test_listing_pages_render_filters_and_export_script(): void
    {
        $this->login();
        foreach (['admin.vehicles.index', 'admin.sales.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('PVP mínimo')->assertSee('complete-list-export.js');
        }
    }

    public function test_zero_is_a_real_bound_and_access_is_required(): void
    {
        $this->login();
        $prefix = uniqid('ZERO');
        Vehicle::create(['license' => $prefix, 'pvp' => 0]);
        Vehicle::create(['license' => $prefix, 'pvp' => 1]);
        $this->getJson(route('admin.vehicles.index', ['amount_max' => 0, 'search' => ['value' => $prefix]]),
            ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->actingAs(User::factory()->create())->getJson(route('admin.vehicles.index'))->assertForbidden();
    }

    public function test_cash_export_includes_all_filtered_rows_and_protects_formulas(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['title' => 'Admin']));
        $this->actingAs($user);
        $prefix = uniqid('CSV');
        for ($i = 0; $i < 55; $i++) {
            \App\Models\AccountOperation::create(['description' => $prefix.'-'.$i, 'total' => 10,
                'movement_type' => 'income', 'qty' => 1, 'date' => '2026-11-06', 'notes' => '=1+1']);
        }
        \App\Models\AccountOperation::create(['description' => $prefix.'-excluded', 'total' => 100,
            'movement_type' => 'income', 'qty' => 1, 'date' => '2026-11-06']);
        $response = $this->get(route('admin.cash.export', ['q' => $prefix, 'amount_max' => 10]))->assertOk();
        $csv = $response->streamedContent();
        $this->assertSame(55, substr_count($csv, $prefix));
        $this->assertStringNotContainsString('excluded', $csv);
        $this->assertStringContainsString("'=1+1", $csv);
        $this->actingAs(User::factory()->create())->get(route('admin.cash.export'))->assertForbidden();
    }
}
