<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\GeneralState;
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

    public function test_stock_excludes_sold_states_without_dates_and_matches_dashboard_count(): void
    {
        $admin = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $expectedIds = [];
        foreach (['Em stock disponível', 'Oficina', 'Free Rent', 'Adjudicação', 'VENDA SUSPENSA'] as $name) {
            $state = GeneralState::firstOrCreate(['name' => $name]);
            $expectedIds[] = Vehicle::create(['model' => 'DASHFILTER '.$name, 'general_state_id' => $state->id])->id;
        }
        foreach (['VENDIDO STAND', 'vendido salvados', 'VENDIDO OFICINA', 'VENDIDO A COMÉRCIO', ' Vendida ', 'Vendidos'] as $name) {
            $state = GeneralState::firstOrCreate(['name' => $name]);
            Vehicle::create(['model' => 'DASHFILTER SOLD', 'general_state_id' => $state->id]);
        }
        $archivedState = GeneralState::create(['name' => 'VENDIDO TESTE ARQUIVADO']);
        Vehicle::create(['model' => 'DASHFILTER ARCHIVED STATE', 'general_state_id' => $archivedState->id]);
        $archivedState->delete();
        Vehicle::create(['model' => 'DASHFILTER SALE DATE', 'sale_date' => now()->toDateString()]);
        $deleted = Vehicle::create(['model' => 'DASHFILTER DELETED']);
        $deleted->delete();

        $this->assertEqualsCanonicalizing($expectedIds, $this->filteredVehicleIds($admin, 'stock'));

        $count = Vehicle::query()->inStock()->count();
        $this->actingAs($admin)->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.vehicles.index', ['dashboard_filter' => 'stock', 'draw' => 1, 'start' => 0, 'length' => 1]))
            ->assertOk()->assertJsonPath('recordsTotal', $count)->assertJsonPath('recordsFiltered', $count);
        $this->flushHeaders();
        $this->actingAs($admin)->get(route('admin.home'))->assertOk()
            ->assertViewHas('business', fn ($business) => $business['stock_count'] === $count);
    }

    public function test_stock_filter_reacts_to_sale_date_and_state_without_changing_vehicle_data(): void
    {
        $vehicle = Vehicle::create(['model' => 'DASHFILTER TRANSITIONS']);
        $vehicle->refresh();
        $before = $vehicle->getRawOriginal();
        $this->assertTrue(Vehicle::inStock()->whereKey($vehicle->id)->exists());
        $this->assertSame($before, $vehicle->fresh()->getRawOriginal());
        $state = GeneralState::firstOrCreate(['name' => 'VENDIDO STAND']);
        $vehicle->update(['general_state_id' => $state->id]);
        $this->assertFalse(Vehicle::inStock()->whereKey($vehicle->id)->exists());
        $vehicle->update(['general_state_id' => null, 'sale_date' => now()->toDateString()]);
        $this->assertFalse(Vehicle::inStock()->whereKey($vehicle->id)->exists());
        $vehicle->update(['sale_date' => null]);
        $this->assertTrue(Vehicle::inStock()->whereKey($vehicle->id)->exists());
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
