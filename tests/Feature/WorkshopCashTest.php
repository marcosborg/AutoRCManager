<?php

namespace Tests\Feature;

use App\Models\AccountOperation;
use App\Models\CashBox;
use App\Models\CashCategory;
use App\Models\CashTransfer;
use App\Models\Role;
use App\Models\User;
use App\Services\CashBalanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkshopCashTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_can_transfer_money_to_workshop_without_a_proof(): void
    {
        $user = $this->userWithRole('Gestão');
        [$source, $workshop] = $this->fundedBoxes(1000);

        $this->actingAs($user)->post(route('admin.workshop-cash.transfers.store'), [
            'from_cash_box_id' => $source->id,
            'to_cash_box_id' => $workshop->id,
            'total' => 500,
            'occurred_at' => '2026-07-14 15:00:00',
            'notes' => 'Reforço semanal',
        ])->assertRedirect(route('admin.workshop-cash.index'))->assertSessionHasNoErrors();

        $transfer = CashTransfer::query()->latest('id')->firstOrFail();
        $this->assertSame(2, $transfer->operations()->count());
        $this->assertSame($user->id, $transfer->created_by_id);
        $this->assertSame(500.0, app(CashBalanceService::class)->balance($workshop));
        $this->assertSame(500.0, app(CashBalanceService::class)->balance($source));
        $this->assertCount(0, $transfer->proofs);
    }

    public function test_workshop_can_register_a_documented_expense_with_multiple_proofs(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('Chefe oficina');
        [, $workshop] = $this->fundedBoxes(1000, 1000);
        $category = $this->workshopCategory($workshop, 'Peças');

        $this->actingAs($user)->post(route('admin.workshop-cash.expenses.store'), [
            'cash_category_id' => $category->id,
            'total' => 185,
            'date' => '2026-07-14',
            'notes' => 'Compra de peças',
            'proofs' => [
                UploadedFile::fake()->image('fatura.jpg'),
                UploadedFile::fake()->create('recibo.pdf', 100, 'application/pdf'),
            ],
        ])->assertRedirect(route('admin.workshop-cash.index'))->assertSessionHasNoErrors();

        $expense = AccountOperation::query()->where('cash_box_id', $workshop->id)->where('movement_type', 'outcome')->latest('id')->firstOrFail();
        $this->assertSame($user->id, $expense->created_by_id);
        $this->assertCount(2, $expense->proofs);
        $this->assertSame(815.0, app(CashBalanceService::class)->balance($workshop));
    }

    public function test_expense_requires_a_proof_and_sufficient_balance(): void
    {
        $user = $this->userWithRole('Chefe oficina');
        [, $workshop] = $this->fundedBoxes(100, 100);
        $category = $this->workshopCategory($workshop, 'Consumíveis');

        $this->actingAs($user)->post(route('admin.workshop-cash.expenses.store'), [
            'cash_category_id' => $category->id,
            'total' => 10,
            'date' => '2026-07-14',
        ])->assertSessionHasErrors('proofs');

        $this->actingAs($user)->post(route('admin.workshop-cash.expenses.store'), [
            'cash_category_id' => $category->id,
            'total' => 101,
            'date' => '2026-07-14',
            'proofs' => [UploadedFile::fake()->image('fatura.jpg')],
        ])->assertSessionHasErrors('total');

        $this->assertSame(0, AccountOperation::query()->where('cash_box_id', $workshop->id)->where('movement_type', 'outcome')->count());
    }

    public function test_transfer_cannot_overdraw_the_source_box(): void
    {
        $user = $this->userWithRole('Gestão');
        [$source, $workshop] = $this->fundedBoxes(100);

        $this->actingAs($user)->post(route('admin.workshop-cash.transfers.store'), [
            'from_cash_box_id' => $source->id,
            'to_cash_box_id' => $workshop->id,
            'total' => 101,
            'occurred_at' => '2026-07-14 15:00:00',
        ])->assertSessionHasErrors('total');

        $this->assertSame(0, CashTransfer::query()->count());
    }

    public function test_permissions_separate_transfers_and_expenses(): void
    {
        [$source, $workshop] = $this->fundedBoxes(1000, 1000);
        $category = $this->workshopCategory($workshop, 'Diversos');

        $this->actingAs($this->userWithRole('Chefe oficina'))->post(route('admin.workshop-cash.transfers.store'), [
            'from_cash_box_id' => $source->id,
            'to_cash_box_id' => $workshop->id,
            'total' => 10,
            'occurred_at' => now(),
        ])->assertForbidden();

        $this->actingAs($this->userWithRole('Gestão'))->post(route('admin.workshop-cash.expenses.store'), [
            'cash_category_id' => $category->id,
            'total' => 10,
            'date' => now()->toDateString(),
            'proofs' => [UploadedFile::fake()->image('proof.jpg')],
        ])->assertForbidden();
    }

    public function test_general_cash_form_cannot_create_a_manual_workshop_movement(): void
    {
        $admin = $this->userWithRole('Admin');
        $workshop = CashBox::query()->where('slug', 'caixa_oficina')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.cash.movements.store'), [
            'movement_type' => 'income',
            'date' => now()->toDateString(),
            'description' => 'Entrada manual',
            'total' => 100,
            'cash_box_id' => $workshop->id,
        ])->assertSessionHasErrors('cash_box_id');

        $this->assertSame(0, AccountOperation::query()->where('cash_box_id', $workshop->id)->where('description', 'Entrada manual')->count());
    }

    public function test_initial_workshop_categories_are_available(): void
    {
        $workshop = CashBox::query()->where('slug', 'caixa_oficina')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['Peças', 'Oficina Externa', 'Horas Extra Mecânicos', 'Consumíveis', 'Diversos'],
            CashCategory::query()->where('cash_box_id', $workshop->id)->pluck('name')->all(),
        );
    }

    public function test_authorized_user_can_view_balance_history_and_proofs(): void
    {
        $user = $this->userWithRole('Gestão');
        [, $workshop] = $this->fundedBoxes(1000, 250);

        $this->actingAs($user)
            ->get(route('admin.workshop-cash.index'))
            ->assertOk()
            ->assertSee('Caixa da Oficina')
            ->assertSee('250,00 €')
            ->assertSee('Saldo inicial teste');

        $this->assertSame(250.0, app(CashBalanceService::class)->balance($workshop));
    }

    private function fundedBoxes(float $sourceAmount, float $workshopAmount = 0): array
    {
        $source = CashBox::query()->where('slug', 'caixa_1')->firstOrFail();
        $workshop = CashBox::query()->where('slug', 'caixa_oficina')->firstOrFail();
        AccountOperation::query()->whereIn('cash_box_id', [$source->id, $workshop->id])->delete();
        AccountOperation::create(['description' => 'Saldo inicial', 'movement_type' => 'income', 'total' => $sourceAmount, 'cash_box_id' => $source->id, 'qty' => 1, 'date' => now()]);
        if ($workshopAmount > 0) {
            AccountOperation::create(['description' => 'Saldo inicial teste', 'movement_type' => 'income', 'total' => $workshopAmount, 'cash_box_id' => $workshop->id, 'qty' => 1, 'date' => now()]);
        }

        return [$source, $workshop];
    }

    private function workshopCategory(CashBox $workshop, string $name): CashCategory
    {
        return CashCategory::query()->where('cash_box_id', $workshop->id)->where('name', $name)->firstOrFail();
    }

    private function userWithRole(string $roleTitle): User
    {
        $role = Role::query()->where('title', $roleTitle)->firstOrFail();
        $user = User::query()->create(['name' => 'Cash '.$roleTitle, 'email' => uniqid('cash-', true).'@example.test', 'password' => 'password']);
        $user->roles()->attach($role);

        return $user;
    }
}
