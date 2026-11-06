<?php
namespace Tests\Feature;

use App\Models\{Vehicle, Client, Permission, Role, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VehicleSaleProtectionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stock_vehicle_can_be_removed_but_sold_vehicle_cannot(): void
    {
        $stock = Vehicle::create([]);
        $stock->delete();
        $this->assertSoftDeleted($stock);
        $sold = Vehicle::create(['sale_date' => '2026-11-06']);
        $this->expectException(ValidationException::class);
        $sold->delete();
    }

    public function test_bulk_removal_is_all_or_nothing(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Protection ')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'vehicle_delete'])->id);
        $user->roles()->attach($role);
        $stock = Vehicle::create([]);
        $reserved = Vehicle::create(['client_id' => Client::create(['name' => 'Teste'])->id]);
        $this->actingAs($user)->deleteJson(route('admin.vehicles.massDestroy'), ['ids' => [$stock->id, $reserved->id]])->assertUnprocessable();
        $this->assertNull($stock->fresh()->deleted_at);
        $this->assertNull($reserved->fresh()->deleted_at);
        $this->deleteJson(route('admin.vehicles.destroy', $reserved))->assertUnprocessable();
    }

    public function test_exit_requires_sale_invoice_not_purchase_invoice_or_annulled_file(): void
    {
        $v = Vehicle::create([]);
        foreach ([null, 'purchase_invoice', 'annulled'] as $kind) {
            if ($kind) $this->invoice($v, $kind === 'annulled' ? 'sale_invoice' : $kind, $kind === 'annulled');
            try {
                $v->update(['sele_chekout' => '2026-11-06']);
                $this->fail('Exit should be blocked');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('sele_chekout', $e->errors());
            }
            $v->refresh();
            $this->assertNull($v->sele_chekout);
        }
        $this->invoice($v, 'sale_invoice');
        $v->update(['sele_chekout' => '2026-11-06']);
        $this->assertNotNull($v->fresh()->sele_chekout);
    }

    private function invoice(Vehicle $v, string $type, bool $annulled = false): void
    {
        $properties = ['document_classification' => ['type' => $type]];
        if ($annulled) $properties['document_annulment'] = ['reason' => 'Teste'];
        $v->media()->create(['collection_name' => 'documents', 'name' => 'Teste', 'file_name' => 'test.pdf',
            'mime_type' => 'application/pdf', 'disk' => 'public', 'size' => 10,
            'manipulations' => [], 'custom_properties' => $properties, 'generated_conversions' => [], 'responsive_images' => []]);
    }
}
