<?php

namespace Tests\Feature;

use App\Models\{PartOrder, Permission, Role, Suplier, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PartOrderFullExportTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(bool $allowed = true): User
    {
        $role = Role::create(['title' => uniqid('Order export ')]);
        if ($allowed) $role->permissions()->attach(Permission::firstOrCreate(['title' => 'part_order_access'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function rows(array $params): array
    {
        $response = $this->get(route('admin.part-orders.index', $params + ['export' => 'csv']))->assertOk()->assertDownload();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($response->streamedContent(), 3));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) $rows[] = $row;
        fclose($stream);
        $this->assertCount(14, array_shift($rows));
        return $rows;
    }

    public function test_exports_all_pages_with_combined_filters_and_without_writes(): void
    {
        $this->actingAs($this->actor());
        $supplier = Suplier::create(['name' => '=1+1']);
        $vehicle = Vehicle::create(['foreign_license' => uniqid('EXP'), 'model' => 'Modelo']);
        $base = ['vehicle_id' => $vehicle->id, 'suplier_id' => $supplier->id, 'status' => 'ordered', 'priority' => 'urgent',
            'notes' => '+Nota teste', 'requested_by_id' => auth()->id(), 'technician_id' => auth()->id(),
            'expected_delivery_date' => now()->subDay()->toDateString()];
        $ids = [];
        for ($i = 0; $i < 53; $i++) $ids[] = PartOrder::create($base)->id;
        PartOrder::create(array_replace($base, ['status' => 'received']));
        PartOrder::create(array_replace($base, ['priority' => 'normal']));
        PartOrder::create(array_replace($base, ['expected_delivery_date' => now()->addDay()->toDateString()]));
        $deleted = PartOrder::create($base);
        $deleted->delete();
        $before = PartOrder::whereIn('id', $ids)->get()->toArray();
        $params = ['vehicle_search' => $vehicle->foreign_license, 'suplier_id' => $supplier->id,
            'status' => 'ordered', 'priority' => 'urgent', 'delayed' => 1, 'page' => 2];
        $rows = $this->rows($params);
        $this->assertSame(array_reverse($ids), array_map(fn ($row) => (int) $row[0], $rows));
        $this->assertSame("'=1+1", $rows[0][5]);
        $this->assertSame('Urgente', $rows[0][7]);
        $this->assertSame(auth()->user()->name, $rows[0][10]);
        $this->assertSame(auth()->user()->name, $rows[0][11]);
        $this->assertSame("'+Nota teste", $rows[0][13]);
        $this->assertSame($before, PartOrder::whereIn('id', $ids)->get()->toArray());
        $this->get(route('admin.part-orders.index', $params))->assertOk()->assertSee('Exportar todos os resultados (CSV)');
    }

    public function test_empty_results_and_access_controls(): void
    {
        $this->actingAs($this->actor());
        $this->assertSame([], $this->rows(['vehicle_search' => uniqid('NOMATCH') ]));
        $this->getJson(route('admin.part-orders.index', ['export' => 'invalid']))->assertUnprocessable();
        $this->actingAs($this->actor(false))->get(route('admin.part-orders.index', ['export' => 'csv']))->assertForbidden();
    }
}
