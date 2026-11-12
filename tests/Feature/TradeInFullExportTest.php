<?php

namespace Tests\Feature;

use App\Models\{Permission, Role, User, VehicleTradeIn};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TradeInFullExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_export_includes_all_filtered_pages_without_writes_and_preserves_access(): void
    {
        $role = Role::create(['title' => uniqid('Export')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'vehicle_trade_in_convert'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        $this->actingAs($user);
        $prefix = uniqid('EXPORT');
        $base = ['license' => $prefix, 'normalized_license' => $prefix, 'amount' => 10.50,
            'status' => 'converted', 'converted_at' => now(), 'created_by_id' => $user->id,
            'notes' => '=1+1', 'has_ipo' => true];
        $ids = [];
        for ($i = 0; $i < 53; $i++) $ids[] = VehicleTradeIn::create(array_replace($base, ['license' => $prefix.$i, 'normalized_license' => $prefix.$i]))->id;
        VehicleTradeIn::create(array_replace($base, ['amount' => 21]));
        VehicleTradeIn::create(array_replace($base, ['status' => 'pending']));
        VehicleTradeIn::create(array_replace($base, ['converted_at' => now()->subDays(2)]));
        $deleted = VehicleTradeIn::create($base);
        $deleted->delete();
        $before = VehicleTradeIn::whereIn('id', $ids)->get()->toArray();
        $params = ['export' => 'csv', 'page' => 2, 'status' => 'converted', 'search' => $prefix,
            'date_start' => now()->toDateString(), 'date_end' => now()->toDateString(),
            'amount_min' => '10,50', 'amount_max' => '20,75'];
        $response = $this->get(route('admin.vehicle-trade-ins.index', $params))->assertOk()->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($content, 3)); rewind($stream);
        $this->assertCount(20, fgetcsv($stream, 0, ';', '"', ''));
        $found = [];
        while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
            $found[] = (int) $row[0];
            $this->assertSame('10,50', $row[3]);
            $this->assertSame("'=1+1", $row[13]);
            $this->assertSame('Sim', $row[17]);
            $this->assertNotEmpty($row[8]);
        }
        fclose($stream);
        $this->assertSame(array_reverse($ids), $found);
        $this->assertSame($before, VehicleTradeIn::whereIn('id', $ids)->get()->toArray());
        $this->getJson(route('admin.vehicle-trade-ins.index', ['export' => 'bad']))->assertUnprocessable();
        $this->getJson(route('admin.vehicle-trade-ins.index', ['export' => 'csv', 'amount_min' => 20, 'amount_max' => 10]))->assertUnprocessable();
        $role->permissions()->sync([Permission::firstOrCreate(['title' => 'vehicle_trade_in_access'])->id]);
        $this->actingAs($user->fresh())->get(route('admin.vehicle-trade-ins.index', $params))->assertOk()->assertDownload();
        $this->get(route('admin.vehicle-trade-ins.index', array_replace($params, ['status' => 'pending'])))->assertForbidden();
        $user->roles()->detach();
        $this->actingAs($user->fresh())->get(route('admin.vehicle-trade-ins.index', $params))->assertForbidden();
    }
}
