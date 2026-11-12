<?php

namespace Tests\Feature;

use App\Models\{PartOrder, PartPayment, Permission, Role, Suplier, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PartPaymentFullExportTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(bool $allowed = true): User
    {
        $role = Role::create(['title' => uniqid('Payment export ')]);
        if ($allowed) $role->permissions()->attach(Permission::firstOrCreate(['title' => 'part_payment_access'])->id);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function rows(array $params): array
    {
        $response = $this->get(route('admin.part-payments.index', $params + ['export' => 'csv']))->assertOk()->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $this->assertCount(6, fgetcsv($stream, 0, ';', '"', ''));
        $rows = [];
        while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) $rows[] = $row;
        fclose($stream);
        return $rows;
    }

    public function test_all_filtered_pages_export_without_changing_payments(): void
    {
        $this->actingAs($this->actor());
        $supplier = Suplier::create(['name' => '=1+1']);
        $order = PartOrder::create(['status' => 'ordered', 'priority' => 'normal']);
        $base = ['part_order_id' => $order->id, 'suplier_id' => $supplier->id, 'payment_status' => 'pending', 'amount' => 1234.56,
            'due_date' => now()->subDay()->toDateString()];
        $ids = [];
        for ($i = 0; $i < 53; $i++) $ids[] = PartPayment::create($base)->id;
        PartPayment::create(array_replace($base, ['payment_status' => 'paid']));
        PartPayment::create(array_replace($base, ['payment_status' => 'cancelled']));
        PartPayment::create(array_replace($base, ['due_date' => now()->addDay()->toDateString()]));
        PartPayment::create(array_replace($base, ['suplier_id' => null]));
        $deleted = PartPayment::create($base);
        $deleted->delete();
        $before = PartPayment::whereIn('id', $ids)->get()->toArray();
        $params = ['suplier_id' => $supplier->id, 'payment_status' => 'pending', 'overdue' => 1, 'page' => 2];
        $rows = $this->rows($params);
        $this->assertSame(array_reverse($ids), array_map(fn ($row) => (int) $row[0], $rows));
        $this->assertSame("'=1+1", $rows[0][2]);
        $this->assertSame('1234,56', $rows[0][3]);
        $this->assertSame('Pendente', $rows[0][5]);
        $this->assertSame($before, PartPayment::whereIn('id', $ids)->get()->toArray());
        $this->get(route('admin.part-payments.index', $params))->assertOk()->assertSee('Exportar todos os resultados (CSV)');
    }

    public function test_empty_results_and_access_controls(): void
    {
        $this->actingAs($this->actor());
        $supplier = Suplier::create(['name' => uniqid('Empty ')]);
        $this->assertSame([], $this->rows(['suplier_id' => $supplier->id]));
        $this->getJson(route('admin.part-payments.index', ['export' => 'invalid']))->assertUnprocessable();
        $this->actingAs($this->actor(false))->get(route('admin.part-payments.index', ['export' => 'csv']))->assertForbidden();
    }
}
