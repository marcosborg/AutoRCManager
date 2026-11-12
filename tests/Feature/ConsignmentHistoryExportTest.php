<?php
namespace Tests\Feature;

use App\Models\{Permission, Role, User, VehicleConsignmentAudit};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConsignmentHistoryExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_all_filtered_history_is_exported_read_only(): void
    {
        $role = Role::create(['title' => uniqid('Export')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'vehicle_consignment_access'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        $this->actingAs($user);
        $license = strtoupper(uniqid('TEST'));
        $base = ['action' => 'updated', 'vehicle_license_before' => $license, 'vehicle_license_after' => $license,
            'user_name' => '=1+1', 'created_at' => now(), 'effective_starts_at' => '2026-01-01',
            'before' => ['status' => 'active'], 'after' => ['status' => 'closed']];
        $ids = [];
        for ($i = 0; $i < 103; $i++) $ids[] = VehicleConsignmentAudit::create($base)->id;
        VehicleConsignmentAudit::create(array_replace($base, ['effective_ends_at' => '2026-01-02']));
        VehicleConsignmentAudit::create(array_replace($base, ['vehicle_license_before' => 'OTHER', 'vehicle_license_after' => 'OTHER']));
        $before = VehicleConsignmentAudit::whereIn('id', $ids)->get()->toArray();
        $params = ['export' => 'csv', 'page' => 2, 'license' => $license, 'occurrence_date' => '2026-11-12'];
        $response = $this->get(route('admin.vehicle-consignments.history', $params))->assertOk()->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'r+'); fwrite($stream, substr($content, 3)); rewind($stream);
        $this->assertCount(12, fgetcsv($stream, 0, ';', '"', ''));
        $found = [];
        while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
            $found[] = (int) $row[0];
            $this->assertSame("'=1+1", $row[6]);
            $this->assertSame('status: active → closed', $row[11]);
        }
        fclose($stream);
        $this->assertSame(array_reverse($ids), $found);
        $this->assertSame($before, VehicleConsignmentAudit::whereIn('id', $ids)->get()->toArray());
        $this->getJson(route('admin.vehicle-consignments.history', ['export' => 'bad']))->assertUnprocessable();
        $user->roles()->detach();
        $this->actingAs($user->fresh())->get(route('admin.vehicle-consignments.history', $params))->assertForbidden();
    }
}
