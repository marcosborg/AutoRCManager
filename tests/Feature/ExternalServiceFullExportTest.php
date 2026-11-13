<?php
namespace Tests\Feature;

use App\Models\{ExternalService, Permission, Role, Suplier, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExternalServiceFullExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_export_respects_all_filters_and_ignores_pagination_without_writes(): void
    {
        $role = Role::create(['title' => uniqid('Export ')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'external_service_access'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        $this->actingAs($user);
        $vehicle = Vehicle::create(['foreign_license' => uniqid('EXT')]);
        $supplier = Suplier::create(['name' => '=1+1']);
        $base = ['vehicle_id' => $vehicle->id, 'suplier_id' => $supplier->id, 'description' => '+Teste',
            'status' => 'scheduled', 'priority' => 'normal', 'amount' => 10.50, 'expected_date' => '2026-12-01'];
        $ids = [];
        for ($i = 0; $i < 35; $i++) $ids[] = ExternalService::create($base)->id;
        ExternalService::create(array_replace($base, ['amount' => 21]));
        ExternalService::create(array_replace($base, ['status' => 'cancelled']));
        ExternalService::create(array_replace($base, ['suplier_id' => null]));
        $deleted = ExternalService::create($base);
        $deleted->delete();
        $before = ExternalService::whereIn('id', $ids)->get()->toArray();
        $params = ['vehicle_search' => $vehicle->foreign_license, 'suplier_id' => $supplier->id, 'status' => 'scheduled',
            'amount_min' => '10,50', 'amount_max' => '20,75', 'page' => 2, 'export' => 'csv'];
        $response = $this->get(route('admin.external-services.index', $params))->assertOk()->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($content, 3)); rewind($stream);
        $this->assertCount(11, fgetcsv($stream, 0, ';', '"', ''));
        $found = [];
        while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
            $found[] = (int) $row[0];
            $this->assertSame($vehicle->foreign_license, $row[2]);
            $this->assertSame("'+Teste", $row[3]);
            $this->assertSame("'=1+1", $row[4]);
            $this->assertSame('2026-12-01', $row[6]);
            $this->assertSame('10,50', $row[7]);
        }
        fclose($stream);
        $this->assertSame(array_reverse($ids), $found);
        $this->assertSame($before, ExternalService::whereIn('id', $ids)->get()->toArray());
        $empty = $this->get(route('admin.external-services.index', ['export' => 'csv', 'vehicle_search' => uniqid('NONE')]))->assertOk();
        $this->assertCount(1, preg_split('/\r?\n/', trim($empty->streamedContent())));
        $this->getJson(route('admin.external-services.index', ['export' => 'bad']))->assertUnprocessable();
        $user->roles()->detach();
        $this->actingAs($user->fresh())->get(route('admin.external-services.index', ['export' => 'csv']))->assertForbidden();
    }
}
