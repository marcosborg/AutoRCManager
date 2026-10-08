<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Repair;
use App\Models\RepairPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairPartService
{
    public const FIELDS = ['supplier' => 'Fornecedor', 'invoice_number' => 'Fatura', 'part_date' => 'Data', 'part_name' => 'Nome', 'amount' => 'Valor'];

    public function revision(Repair $repair): string
    {
        return $this->fingerprint($repair->parts()->orderBy('id')->get());
    }

    private function fingerprint($parts): string
    {
        return hash('sha256', $parts->map(fn ($part) => [
            'id' => $part->id, 'values' => $this->snapshot($part),
        ])->toJson());
    }

    public function sync(Repair $repair, array $rows, ?string $revision): void
    {
        DB::transaction(function () use ($repair, $rows, $revision) {
            Repair::whereKey($repair->id)->lockForUpdate()->firstOrFail();
            $parts = $repair->parts()->lockForUpdate()->get()->keyBy('id');
            if (! $revision || ! hash_equals($this->fingerprint($parts->sortKeys()->values()), $revision)) {
                throw ValidationException::withMessages(['repair_parts' => 'As peças foram alteradas ou a página está desatualizada. Atualize a página e confirme os dados antes de gravar.']);
            }
            $ids = array_filter(array_column($rows, 'id'));
            if (count($ids) !== count(array_unique($ids)) || collect($ids)->contains(fn ($id) => ! $parts->has($id))) {
                throw ValidationException::withMessages(['repair_parts' => 'Existe uma peça inválida ou repetida nesta reparação. Atualize a página.']);
            }
            $kept = [];
            $cancellations = [];
            foreach ($rows as $row) {
                if (! empty($row['cancel'])) {
                    $reason = trim((string) ($row['cancellation_reason'] ?? ''));
                    if (empty($row['id']) || mb_strlen($reason) < 3 || mb_strlen($reason) > 500) {
                        throw ValidationException::withMessages(['repair_parts' => 'Para anular uma peça guardada, indique um motivo entre 3 e 500 caracteres.']);
                    }
                    $cancellations[(int) $row['id']] = $reason;
                }
            }
            foreach ($rows as $row) {
                if (! empty($row['cancel'])) continue;
                $values = [];
                foreach (array_keys(self::FIELDS) as $field) {
                    $value = trim((string) ($row[$field] ?? ''));
                    $values[$field] = $value === '' ? null : ($field === 'amount' ? number_format((float) $value, 2, '.', '') : $value);
                }
                if (! array_filter($values, fn ($value) => $value !== null)) continue;
                $part = ! empty($row['id']) ? $parts->get($row['id']) : new RepairPart(['repair_id' => $repair->id]);
                $before = $part->exists ? $this->snapshot($part) : null;
                $part->fill($values);
                if (! $part->exists || $part->isDirty()) {
                    $part->save();
                    $this->record($part, $before, $this->snapshot($part), $before === null ? 'Criação' : 'Alteração');
                }
                $kept[] = $part->id;
            }
            foreach ($parts->except($kept) as $part) {
                if (! isset($cancellations[$part->id])) {
                    throw ValidationException::withMessages(['repair_parts' => 'Uma peça guardada não pode desaparecer da lista. Use Anular e indique o motivo.']);
                }
                $before = $this->snapshot($part);
                $part->delete();
                $this->record($part, $before, null, 'Anulação', $cancellations[$part->id]);
            }
        });
    }

    private function snapshot(RepairPart $part): array
    {
        return [
            'supplier' => $part->supplier, 'invoice_number' => $part->invoice_number,
            'part_date' => $part->part_date?->format('Y-m-d'), 'part_name' => $part->part_name,
            'amount' => $part->amount === null ? null : number_format((float) $part->amount, 2, '.', ''),
        ];
    }

    private function record(RepairPart $part, ?array $before, ?array $after, string $operation, ?string $reason = null): void
    {
        AuditLog::create([
            'description' => 'repair_part:changed', 'subject_id' => $part->id,
            'subject_type' => RepairPart::class.'#'.$part->id, 'user_id' => auth()->id(), 'host' => request()->ip(),
            'properties' => ['repair_id' => $part->repair_id, 'operation' => $operation,
                'actor_name' => auth()->user()?->name, 'before' => $before, 'after' => $after, 'reason' => $reason],
        ]);
    }
}
