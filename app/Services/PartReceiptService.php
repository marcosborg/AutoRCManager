<?php

namespace App\Services;

use App\Models\PartOrder;
use App\Models\PartReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartReceiptService
{
    public function save(array $data, ?PartReceipt $receipt = null, bool $delete = false): PartReceipt
    {
        return DB::transaction(function () use ($data, $receipt, $delete) {
            $order = PartOrder::whereKey($data['part_order_id'])->lockForUpdate()->first();
            if (! $order || $order->status === 'cancelled') {
                $this->invalid('A encomenda já não está disponível para receção.');
            }
            if ($receipt) {
                $receipt = PartReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
                if ((int) $receipt->part_order_id !== (int) $order->id) {
                    $this->invalid('Não é possível mudar a encomenda de uma receção existente.');
                }
            }
            if ($receipt && $receipt->received_items !== null && ($data['receipt_revision'] ?? '') !== $this->revision($receipt)) {
                $this->invalid('Esta receção foi alterada. Atualize a página antes de gravar.');
            }
            $items = $order->items()->lockForUpdate()->get()->keyBy('id');
            $previous = collect($receipt?->received_items ?? [])->keyBy('id');
            // Old receipts contain no item breakdown. Keep their historical meaning unchanged.
            if ($receipt && $receipt->received_items === null) {
                if ($delete || ! empty($data['quantities'])) {
                    $this->invalid('Esta receção antiga não tem quantidades discriminadas. Pode corrigir os dados, mas não recalcular a entrega.');
                }
            } else {
                $quantities = $delete ? [] : ($data['quantities'] ?? []);
                $positive = array_filter($quantities, fn ($quantity) => (float) $quantity > 0);
                if (! $delete && ! $positive) {
                    $this->invalid('Indique a quantidade que chegou de pelo menos uma peça.');
                }
                $snapshot = [];
                foreach (array_unique(array_merge(array_keys($positive), $previous->keys()->all())) as $id) {
                    $item = $items->get($id);
                    if (! $item) {
                        $this->invalid('Uma das peças já não permite alterar a receção. Atualize a página.');
                    }
                    $quantity = round((float) ($positive[$id] ?? 0), 2);
                    if (in_array($item->status, ['installed', 'returned'], true)) {
                        if ($quantity !== (float) ($previous->get($id)['quantity'] ?? 0)) {
                            $this->invalid('Não é possível alterar a receção de uma peça instalada ou devolvida.');
                        }
                        if ($quantity > 0) {
                            $snapshot[] = $previous->get($id);
                        }
                        continue;
                    }
                    $total = round($item->receivedAmount() - (float) ($previous->get($id)['quantity'] ?? 0) + $quantity, 2);
                    if ($total < 0 || $total > (float) $item->quantity) {
                        $this->invalid('A quantidade recebida excede o que falta na peça “'.$item->description.'”. Atualize a página.');
                    }
                    $item->forceFill(['received_quantity' => $total]);
                    $item->status = $total >= (float) $item->quantity ? 'received' : ($total > 0 ? 'partially_received' : 'ordered');
                    $item->save();
                    if ($quantity > 0) {
                        $snapshot[] = ['id' => $item->id, 'description' => $item->description, 'quantity' => $quantity];
                    }
                }
                $receipt ??= new PartReceipt();
                $receipt->received_items = $snapshot;
            }
            unset($data['quantities'], $data['attachments'], $data['receipt_revision']);
            $receipt->fill($data);
            $delete ? $receipt->delete() : $receipt->save();
            $order->refreshReceiptStatus();
            return $receipt;
        });
    }

    public function revision(PartReceipt $receipt): string
    {
        return hash('sha256', json_encode($receipt->fresh()->getAttributes()));
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['quantities' => $message]);
    }
}
