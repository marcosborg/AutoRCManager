<?php

namespace Tests\Feature;

use App\Models\AccountOperation;
use App\Models\AuditLog;
use App\Models\CashBox;
use App\Models\Role;
use App\Models\User;
use App\Services\CashBalanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CashBoxRenameTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->sync([Role::firstOrCreate(['title' => $role])->id]);
        return $user;
    }

    private function box(): CashBox
    {
        return CashBox::create(['name' => 'Teste '.uniqid(), 'is_active' => true]);
    }

    public function test_rename_preserves_identity_operations_and_balance_and_records_audit(): void
    {
        $user = $this->actor('Admin');
        $box = $this->box();
        $oldName = $box->name;
        $slug = $box->slug;
        $operation = AccountOperation::create(['description' => 'Teste', 'movement_type' => 'income',
            'total' => 123, 'cash_box_id' => $box->id, 'qty' => 1, 'date' => now()]);
        $balance = app(CashBalanceService::class)->balance($box);
        $this->actingAs($user)->patch(route('admin.cash.boxes.rename', $box), [
            'name' => '  Caixa renomeada '. $box->id.'  ', 'expected_name' => $oldName,
            'slug' => 'nao_mudar', 'is_active' => false,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Caixa renomeada '.$box->id, $box->fresh()->name);
        $this->assertSame($slug, $box->fresh()->slug);
        $this->assertTrue($box->fresh()->is_active);
        $this->assertEquals($box->id, $operation->fresh()->cash_box_id);
        $this->assertSame($balance, app(CashBalanceService::class)->balance($box->fresh()));
        $audit = AuditLog::where('description', 'cash_box:renamed')->where('subject_id', $box->id)->firstOrFail();
        $this->assertEquals($user->id, $audit->user_id);
        $this->assertSame($oldName, $audit->properties['before']['name']);
    }

    public function test_rejects_empty_duplicate_and_stale_names(): void
    {
        $box = $this->box();
        $other = $this->box();
        $this->actingAs($this->actor('Adm'));
        foreach ([['name' => '   ', 'expected_name' => $box->name],
            ['name' => ' '.$other->name.' ', 'expected_name' => $box->name],
            ['name' => 'Nome novo', 'expected_name' => 'Nome antigo']] as $payload) {
            $this->patch(route('admin.cash.boxes.rename', $box), $payload)->assertSessionHasErrors('name');
            $this->assertSame($box->name, $box->fresh()->name);
        }
    }

    public function test_unauthorized_user_cannot_rename(): void
    {
        $box = $this->box();
        $this->actingAs($this->actor('Stand'))->patch(route('admin.cash.boxes.rename', $box), [
            'name' => 'Nome novo', 'expected_name' => $box->name,
        ])->assertForbidden();
        $this->assertSame($box->name, $box->fresh()->name);
    }

    public function test_cash_page_renders_rename_controls(): void
    {
        $box = $this->box();
        $this->actingAs($this->actor('Admin'))->get(route('admin.cash.index'))
            ->assertOk()->assertSee('Alterar nomes das caixas')->assertSee(route('admin.cash.boxes.rename', $box));
    }
}
