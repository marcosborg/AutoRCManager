<?php

namespace Tests\Feature;

use App\Models\{PartOrder, PartReceipt, Permission, Role, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PartReceiptFullExportTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(bool $allowed = true): User
    {
        $role = Role::create(['title' => uniqid('Receipt export ')]);
        if ($allowed) $role->permissions()->attach(Permission::firstOrCreate(['title' => 'part_receipt_access'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        return $user;
    }

    public function test_all_pages_are_exported_once_without_writes_or_deleted_receipts(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $order = PartOrder::create(['status' => 'ordered', 'priority' => 'normal']);
        $ids = [];
        for ($i = 0; $i < 53; $i++) {
            $ids[] = PartReceipt::create(['part_order_id' => $order->id, 'received_at' => '2026-11-01 12:00:00',
                'received_location' => '=1+1', 'received_by_id' => $user->id])->id;
        }
        $deleted = PartReceipt::create(['part_order_id' => $order->id]);
        $deleted->delete();
        $before = PartReceipt::whereIn('id', $ids)->get()->toArray();
        $response = $this->get(route('admin.part-receipts.index', ['export' => 'csv', 'page' => 2]))->assertOk()->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $this->assertCount(5, fgetcsv($stream, 0, ';', '"', ''));
        $found = [];
        while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
            $this->assertNotSame((string) $deleted->id, $row[0]);
            if (in_array((int) $row[0], $ids, true)) {
                $found[] = (int) $row[0];
                $this->assertSame("'=1+1", $row[3]);
                $this->assertSame($user->name, $row[4]);
            }
        }
        fclose($stream);
        $this->assertSame(array_reverse($ids), $found);
        $this->assertSame($before, PartReceipt::whereIn('id', $ids)->get()->toArray());
        $this->get(route('admin.part-receipts.index'))->assertOk()->assertSee('Exportar todos os resultados (CSV)');
    }

    public function test_export_requires_list_permission_and_valid_format(): void
    {
        $this->actingAs($this->actor(false))->get(route('admin.part-receipts.index', ['export' => 'csv']))->assertForbidden();
        $this->actingAs($this->actor())->getJson(route('admin.part-receipts.index', ['export' => 'invalid']))->assertUnprocessable();
    }
}
