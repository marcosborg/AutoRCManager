<?php

namespace App\Services;

use App\Models\AccountOperation;
use App\Models\CashBox;
use App\Models\CashCategory;
use App\Models\CashTransfer;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashBalanceService
{
    public function balance(CashBox|int $cashBox): float
    {
        $cashBoxId = $cashBox instanceof CashBox ? $cashBox->id : $cashBox;

        $totals = AccountOperation::query()
            ->leftJoin('account_items', 'account_items.id', '=', 'account_operations.account_item_id')
            ->where('account_operations.cash_box_id', $cashBoxId)
            ->selectRaw('COALESCE(account_operations.movement_type, account_items.type) as effective_type')
            ->selectRaw('SUM(account_operations.total) as total')
            ->groupByRaw('COALESCE(account_operations.movement_type, account_items.type)')
            ->pluck('total', 'effective_type');
        $income = (float) ($totals[AccountOperation::TYPE_INCOME] ?? 0);
        $outcome = (float) ($totals[AccountOperation::TYPE_OUTCOME] ?? 0);

        return round($income - $outcome, 2);
    }

    public function transfer(
        CashBox $fromBox,
        CashBox $toBox,
        float $amount,
        CarbonInterface $occurredAt,
        User $user,
        ?string $notes = null,
        ?int $departmentId = null,
        ?int $categoryId = null,
    ): CashTransfer {
        return DB::transaction(function () use ($fromBox, $toBox, $amount, $occurredAt, $user, $notes, $departmentId, $categoryId): CashTransfer {
            CashBox::query()->whereKey([$fromBox->id, $toBox->id])->orderBy('id')->lockForUpdate()->get();
            $this->ensureSufficientBalance($fromBox, $amount);

            $groupId = (string) Str::uuid();
            $transfer = CashTransfer::create([
                'from_cash_box_id' => $fromBox->id,
                'to_cash_box_id' => $toBox->id,
                'amount' => $amount,
                'occurred_at' => $occurredAt,
                'created_by_id' => $user->id,
                'notes' => $notes,
                'group_id' => $groupId,
            ]);

            $common = [
                'total' => $amount,
                'department_id' => $departmentId,
                'cash_category_id' => $categoryId,
                'qty' => 1,
                'date' => $occurredAt->toDateString(),
                'notes' => $notes,
                'transfer_group_id' => $groupId,
                'cash_transfer_id' => $transfer->id,
                'created_by_id' => $user->id,
            ];

            AccountOperation::create($common + [
                'description' => 'Transferência para '.$toBox->name,
                'movement_type' => AccountOperation::TYPE_OUTCOME,
                'cash_box_id' => $fromBox->id,
            ]);
            AccountOperation::create($common + [
                'description' => 'Transferência de '.$fromBox->name,
                'movement_type' => AccountOperation::TYPE_INCOME,
                'cash_box_id' => $toBox->id,
            ]);

            return $transfer;
        });
    }

    public function expense(
        CashBox $cashBox,
        CashCategory $category,
        float $amount,
        CarbonInterface $occurredAt,
        User $user,
        ?string $notes = null,
    ): AccountOperation {
        return DB::transaction(function () use ($cashBox, $category, $amount, $occurredAt, $user, $notes): AccountOperation {
            CashBox::query()->whereKey($cashBox->id)->lockForUpdate()->firstOrFail();
            $this->ensureSufficientBalance($cashBox, $amount);

            return AccountOperation::create([
                'description' => $category->name,
                'movement_type' => AccountOperation::TYPE_OUTCOME,
                'total' => $amount,
                'department_id' => null,
                'cash_category_id' => $category->id,
                'cash_box_id' => $cashBox->id,
                'qty' => 1,
                'date' => $occurredAt->toDateString(),
                'notes' => $notes,
                'created_by_id' => $user->id,
            ]);
        });
    }

    public function ensureSufficientBalance(CashBox $cashBox, float $amount): void
    {
        if ($this->balance($cashBox) < $amount) {
            throw ValidationException::withMessages([
                'total' => "A caixa {$cashBox->name} não tem saldo suficiente.",
            ]);
        }
    }
}
