<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardVehicleFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_vehicle_cards_open_the_matching_vehicle_lists(): void
    {
        $admin = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $month = Vehicle::create(['model' => 'DASHFILTER MONTH', 'sale_date' => now()->toDateString()]);
        $year = Vehicle::create(['model' => 'DASHFILTER YEAR', 'sale_date' => now()->startOfYear()->addDay()->toDateString()]);
        $stock = Vehicle::create(['model' => 'DASHFILTER STOCK', 'sale_date' => null]);

        $this->assertSame([$month->id], $this->filteredVehicleIds($admin, 'sold_month'));
        $this->assertEqualsCanonicalizing([$month->id, $year->id], $this->filteredVehicleIds($admin, 'sold_year'));
        $this->assertSame([$stock->id], $this->filteredVehicleIds($admin, 'stock'));

        $this->actingAs($admin)->get(route('admin.home'))
            ->assertOk()
            ->assertSee('dashboard_filter=sold_month', false)
            ->assertSee('dashboard_filter=sold_year', false)
            ->assertSee('dashboard_filter=stock', false);
    }

    private function filteredVehicleIds($user, string $filter): array
    {
        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.vehicles.index', [
                'dashboard_filter' => $filter,
                'draw' => 1,
                'start' => 0,
                'length' => 100,
                'columns' => [[
                    'data' => 'model',
                    'name' => 'model',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => ['value' => '', 'regex' => 'false'],
                ]],
                'search' => ['value' => 'DASHFILTER', 'regex' => 'false'],
            ]))
            ->assertOk();

        return collect($response->json('data'))->pluck('id')->sort()->values()->all();
    }
}
