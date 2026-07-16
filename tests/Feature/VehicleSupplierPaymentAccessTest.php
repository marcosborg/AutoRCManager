<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleSupplierPayment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleSupplierPaymentAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_supplier_payments_are_hidden_and_blocked_for_non_admin_financial_roles(): void
    {
        $role = Role::firstOrCreate(['title' => 'Financeiro de teste']);
        $permissions = collect(['vehicle_edit', 'financial_sensitive_access'])->map(
            fn ($title) => Permission::firstOrCreate(['title' => $title])->id
        );
        $role->permissions()->syncWithoutDetaching($permissions);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        $vehicle = Vehicle::create(['model' => 'Teste pagamentos fornecedor']);
        $paymentMethod = PaymentMethod::firstOrCreate(['name' => 'Método de teste']);
        $payment = VehicleSupplierPayment::create([
            'vehicle_id' => $vehicle->id,
            'paid_at' => now()->toDateString(),
            'amount' => 100,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $this->actingAs($user)->get(route('admin.vehicles.edit', $vehicle))
            ->assertOk()
            ->assertDontSee('Pagamentos ao fornecedor');

        $this->actingAs($user)
            ->delete(route('admin.vehicles.supplier-payments.destroy', [$vehicle, $payment]))
            ->assertForbidden();

        $this->assertDatabaseHas('vehicle_supplier_payments', ['id' => $payment->id]);
    }
}
