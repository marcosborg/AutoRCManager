<?php

namespace Tests\Feature;

use App\Models\{PartOrder, PartReceipt, Permission, Role, User};
use App\Services\PartReceiptService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PartialPartReceiptTest extends TestCase
{
    use DatabaseTransactions;

    public function test_partial_deliveries_finish_only_when_every_quantity_arrives(): void
    {
        $order = $this->order();
        [$a, $b] = $order->items;
        $this->actingAs($this->user());
        $this->post(route('admin.part-receipts.store'), $this->payload($order, [$a->id => 2]))->assertSessionHasNoErrors();
        $this->assertSame('partially_received', $order->fresh()->status);
        $this->assertSame('partially_received', $a->fresh()->status);
        $this->assertSame('ordered', $b->fresh()->status);
        $this->assertNull($order->fresh()->actual_delivery_date);
        $this->post(route('admin.part-receipts.store'), $this->payload($order, [$a->id => 3, $b->id => 1]))->assertSessionHasNoErrors();
        $this->assertSame('received', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->actual_delivery_date);
        $receipt = $order->receipts()->latest('id')->first();
        $this->get(route('admin.part-receipts.edit', $receipt))->assertOk()->assertSee('Outras entregas')->assertSee('Filtro');
        $this->get(route('admin.part-receipts.show', $receipt))->assertOk()->assertSee('Quantidade nesta entrega');
        $this->get(route('admin.part-orders.edit', $order))->assertOk()->assertSee('Pendente');
    }

    public function test_over_receipt_foreign_negative_precision_and_empty_are_rejected_atomically(): void
    {
        $order = $this->order();
        $a = $order->items->first();
        $foreign = $this->order()->items->first();
        $this->actingAs($this->user());
        foreach ([[], [$a->id => 6], [$a->id => -1], [$a->id => 0.001], [$a->id => 1, $foreign->id => 1]] as $quantities) {
            $this->post(route('admin.part-receipts.store'), $this->payload($order, $quantities))->assertSessionHasErrors();
            $this->assertSame(0, $order->receipts()->count());
            $this->assertSame(0.0, $a->fresh()->receivedAmount());
        }
        $order->update(['status' => 'cancelled']);
        $this->post(route('admin.part-receipts.store'), $this->payload($order, [$a->id => 1]))->assertSessionHasErrors();
    }

    public function test_correction_and_deletion_reverse_only_this_delivery_and_stale_edits_fail(): void
    {
        $order = $this->order(); $a = $order->items->first();
        $this->actingAs($this->user());
        $service = app(PartReceiptService::class);
        $first = $service->save($this->payload($order, [$a->id => 2]));
        $second = $service->save($this->payload($order, [$a->id => 2]));
        $payload = $this->payload($order, [$a->id => 1]) + ['receipt_revision' => $service->revision($first)];
        $this->put(route('admin.part-receipts.update', $first), $payload)->assertSessionHasNoErrors();
        $this->assertSame(3.0, $a->fresh()->receivedAmount());
        $this->put(route('admin.part-receipts.update', $first), $payload)->assertSessionHasErrors('quantities');
        $this->delete(route('admin.part-receipts.destroy', $second))->assertRedirect();
        $this->assertSame(1.0, $a->fresh()->receivedAmount());
        $this->delete(route('admin.part-receipts.destroy', $first))->assertRedirect();
        $this->assertSame(0.0, $a->fresh()->receivedAmount());
        $this->assertSame('ordered', $order->fresh()->status);
    }

    public function test_old_receipts_are_preserved_and_users_without_permission_are_blocked(): void
    {
        $order = $this->order();
        $receipt = PartReceipt::create(['part_order_id' => $order->id, 'received_at' => now()]);
        $this->actingAs($this->user());
        $this->put(route('admin.part-receipts.update', $receipt), ['part_order_id' => $order->id, 'observations' => 'Corrigido'])->assertSessionHasNoErrors();
        $this->assertNull($receipt->fresh()->received_items);
        $this->get(route('admin.part-receipts.edit', $receipt))->assertOk()->assertSee('Receção antiga');
        $this->delete(route('admin.part-receipts.destroy', $receipt))->assertSessionHasErrors();
        $this->actingAs(User::factory()->create())->post(route('admin.part-receipts.store'), $this->payload($order, [$order->items->first()->id => 1]))->assertForbidden();
    }

    public function test_received_lines_cannot_be_removed_or_reduced_by_order_edit(): void
    {
        $order = $this->order(); $a = $order->items->first();
        $this->actingAs($this->user());
        app(PartReceiptService::class)->save($this->payload($order, [$a->id => 2]));
        $this->put(route('admin.part-orders.update', $order), ['priority' => 'normal', 'status' => 'ordered', 'items' => []])->assertSessionHasErrors('items');
        $this->assertSame(2, $order->items()->count());
        $rows = $order->items->toArray(); $rows[0]['quantity'] = 1;
        $this->put(route('admin.part-orders.update', $order), ['priority' => 'normal', 'status' => 'ordered', 'items' => $rows])->assertSessionHasErrors('items');
        $this->assertEquals(5, $a->fresh()->quantity);
        $this->assertSame('partially_received', $order->fresh()->status);
    }

    public function test_complete_receipt_correction_reopens_order_and_preserves_other_deliveries(): void
    {
        $order = $this->order(); [$a, $b] = $order->items;
        $service = app(PartReceiptService::class);
        $receipt = $service->save($this->payload($order, [$a->id => 5, $b->id => 1]));
        $this->actingAs($this->user());
        $payload = $this->payload($order, [$a->id => 4, $b->id => 1]) + ['receipt_revision' => $service->revision($receipt)];
        $this->put(route('admin.part-receipts.update', $receipt), $payload)->assertSessionHasNoErrors();
        $this->assertSame('partially_received', $order->fresh()->status);
        $this->assertNull($order->fresh()->actual_delivery_date);
        $this->assertSame('received', $b->fresh()->status);
        $this->post(route('admin.part-receipts.store'), $this->payload($order, [$a->id => 2]))->assertSessionHasErrors();
        $this->assertSame(4.0, $a->fresh()->receivedAmount());
        $b->refresh()->update(['status' => 'installed']);
        $this->delete(route('admin.part-receipts.destroy', $receipt))->assertSessionHasErrors();
        $this->assertSame(4.0, $a->fresh()->receivedAmount());
        $this->assertNotNull($receipt->fresh());
    }

    public function test_receipt_cannot_move_to_another_order_or_change_legacy_received_items(): void
    {
        $order = $this->order(); [$a, $b] = $order->items;
        $a->update(['status' => 'received']);
        $service = app(PartReceiptService::class);
        $receipt = $service->save($this->payload($order, [$b->id => 1]));
        $this->assertNull($a->fresh()->received_quantity);
        $this->assertSame('received', $order->fresh()->status);
        $this->actingAs($this->user())->put(route('admin.part-receipts.update', $receipt), $this->payload($this->order(), [$b->id => 1]) + ['receipt_revision' => $service->revision($receipt)])->assertSessionHasErrors();
        $this->assertSame($order->id, $receipt->fresh()->part_order_id);
    }

    private function order(): PartOrder
    {
        $order = PartOrder::create(['status' => 'ordered', 'priority' => 'normal']);
        $order->items()->create(['description' => 'Filtro', 'quantity' => 5, 'status' => 'ordered']);
        $order->items()->create(['description' => 'Correia', 'quantity' => 1, 'status' => 'ordered']);
        return $order;
    }

    private function payload(PartOrder $order, array $quantities): array
    {
        return ['part_order_id' => $order->id, 'received_at' => '2026-10-08 15:30:00', 'quantities' => $quantities];
    }

    private function user(): User
    {
        $user = User::factory()->create(); $role = Role::create(['title' => uniqid('Receipts ')]);
        foreach (['part_receipt_create', 'part_receipt_edit', 'part_receipt_show', 'part_receipt_delete', 'part_order_edit'] as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        }
        $user->roles()->attach($role); return $user;
    }
}
