<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Client;
use App\Models\GeneralState;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use App\Models\VehicleTradeIn;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleLotPaymentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_edit_page_shares_the_complete_payment_management_area(): void
    {
        $lot = $this->createLot(1250);

        $this->actingAs($this->admin())
            ->get(route('admin.vehicle-groups.edit', $lot))
            ->assertOk()
            ->assertSee('Dados do lote')
            ->assertSee('Liquida&ccedil;&atilde;o e pagamentos', false)
            ->assertSee('Submeter pagamento')
            ->assertSee('Liquidar saldo')
            ->assertSee('Pagamentos')
            ->assertSee('name="return_to" value="edit"', false);

        $this->actingAs($this->admin())
            ->get(route('admin.vehicle-groups.show', $lot))
            ->assertOk()
            ->assertSee('Submeter pagamento')
            ->assertSee('Liquidar saldo')
            ->assertSee('name="return_to" value="show"', false);
    }

    public function test_payment_started_from_edit_returns_to_the_payment_tab(): void
    {
        $lot = $this->createLot(1000);
        $cashMethod = PaymentMethod::query()->where('name', 'Numerário')->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'return_to' => 'edit',
                'payment_type' => 'money',
                'payment_method_id' => $cashMethod->id,
                'paid_at' => $this->paymentDate(),
                'amount' => 250,
                'notes' => 'Pagamento iniciado na edição.',
            ])
            ->assertRedirect(route('admin.vehicle-groups.edit', $lot).'#lot-payments');

        $payment = $lot->payments()->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.approve', [$lot, $payment]), [
                'return_to' => 'edit',
            ])
            ->assertRedirect(route('admin.vehicle-groups.edit', $lot).'#lot-payments');
    }

    public function test_payment_errors_keep_the_edit_payment_tab_active_and_preserve_input(): void
    {
        $lot = $this->createLot(1000);
        $lot->update(['notes' => 'Notas originais do lote.']);
        $bankMethod = PaymentMethod::query()->where('name', 'Transferência bancária')->firstOrFail();

        $this->actingAs($this->admin())
            ->from(route('admin.vehicle-groups.edit', $lot))
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'return_to' => 'edit',
                'payment_type' => 'money',
                'payment_method_id' => $bankMethod->id,
                'paid_at' => $this->paymentDate(),
                'amount' => 175,
                'notes' => 'Notas do pagamento.',
            ])
            ->assertRedirect(route('admin.vehicle-groups.edit', $lot))
            ->assertSessionHasErrors('proof_file')
            ->assertSessionHasInput('return_to', 'edit')
            ->assertSessionHasInput('amount', 175);

        $this->get(route('admin.vehicle-groups.edit', $lot))
            ->assertOk()
            ->assertSee('tab-pane active" id="lot-payments', false)
            ->assertSee('value="175"', false)
            ->assertSee('id="notes">Notas originais do lote.</textarea>', false)
            ->assertSee('name="notes">Notas do pagamento.</textarea>', false);
    }

    public function test_lot_accepts_partial_and_full_cash_payments_without_financial_split(): void
    {
        $lot = $this->createLot(1000);
        $cashMethod = PaymentMethod::query()->where('name', 'Numerário')->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'money',
                'payment_method_id' => $cashMethod->id,
                'paid_at' => $this->paymentDate(),
                'amount' => 400,
                'notes' => 'Pagamento parcial em numerario.',
            ])
            ->assertRedirect(route('admin.vehicle-groups.show', $lot));

        $partialPayment = $lot->payments()->firstOrFail();
        $this->assertSame(400.0, $partialPayment->amount);
        $this->assertSame(0.0, $partialPayment->invoiced_amount);
        $this->assertSame('open', $lot->fresh()->status);

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.approve', [$lot, $partialPayment]))
            ->assertRedirect();

        $this->assertSame('partial', $lot->fresh()->status);

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'money',
                'payment_method_id' => $cashMethod->id,
                'paid_at' => $this->paymentDate(),
                'amount' => 600,
                'invoiced_amount' => 50,
                'notes' => 'Liquidacao final em numerario.',
            ])
            ->assertRedirect(route('admin.vehicle-groups.show', $lot));

        $finalPayment = $lot->payments()->latest('id')->firstOrFail();
        $this->assertSame(600.0, $finalPayment->amount);
        $this->assertSame(50.0, $finalPayment->invoiced_amount);

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.approve', [$lot, $finalPayment]))
            ->assertRedirect();

        $this->assertSame('paid', $lot->fresh()->status);
    }

    public function test_non_cash_payment_requires_proof_and_cash_requires_notes(): void
    {
        Storage::fake('public');

        $lot = $this->createLot(500);
        $bankMethod = PaymentMethod::query()->where('name', 'Transferência bancária')->firstOrFail();
        $cashMethod = PaymentMethod::query()->where('name', 'Numerário')->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'money',
                'payment_method_id' => $bankMethod->id,
                'paid_at' => $this->paymentDate(),
                'amount' => 100,
            ])
            ->assertSessionHasErrors('proof_file');

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'money',
                'payment_method_id' => $cashMethod->id,
                'paid_at' => $this->paymentDate(),
                'amount' => 100,
            ])
            ->assertSessionHasErrors('notes');

        $this->assertSame(0, $lot->payments()->count());

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'money',
                'payment_method_id' => $bankMethod->id,
                'paid_at' => $this->paymentDate(),
                'amount' => 100,
                'proof_file' => UploadedFile::fake()->create('comprovativo.pdf', 50, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.vehicle-groups.show', $lot));

        $this->assertSame(1, $lot->payments()->count());
    }

    public function test_trade_in_payment_creates_and_links_a_stock_vehicle_without_proof(): void
    {
        $lot = $this->createLot(15000);
        $brand = Brand::query()->firstOrFail();
        $license = 'RT'.random_int(1000, 9999);

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'trade_in',
                'paid_at' => $this->paymentDate(),
                'amount' => 7500,
                'trade_in_license' => $license,
                'trade_in_brand_id' => $brand->id,
                'trade_in_model' => 'Modelo de retoma',
                'trade_in_year' => now()->year,
                'trade_in_kilometers' => 45000,
            ])
            ->assertRedirect(route('admin.vehicle-groups.show', $lot));

        $payment = $lot->payments()->with('vehicle_trade_in.created_vehicle')->firstOrFail();

        $this->assertSame('Retoma', $payment->payment_method->name);
        $this->assertNotNull($payment->vehicle_trade_in_id);
        $this->assertSame(VehicleTradeIn::STATUS_PENDING, $payment->vehicle_trade_in->status);
        $this->assertSame(7500.0, $payment->vehicle_trade_in->amount);
        $this->assertNotNull($payment->vehicle_trade_in->created_vehicle);
        $this->assertSame(7500.0, (float) $payment->vehicle_trade_in->created_vehicle->purchase_price);
        $this->assertCount(0, $payment->proof_file);
    }

    public function test_trade_in_payment_uses_an_existing_vehicle(): void
    {
        $lot = $this->createLot(10000);
        $vehicle = Vehicle::query()->whereNotNull('license')->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'trade_in',
                'paid_at' => $this->paymentDate(),
                'amount' => 5000,
                'trade_in_license' => $vehicle->license,
            ])
            ->assertRedirect(route('admin.vehicle-groups.show', $lot));

        $tradeIn = $lot->payments()->firstOrFail()->vehicle_trade_in;

        $this->assertSame($vehicle->id, $tradeIn->created_vehicle_id);
        $this->assertSame(VehicleTradeIn::STATUS_CONVERTED, $tradeIn->status);
        $this->assertSame(5000.0, (float) $vehicle->fresh()->purchase_price);
    }

    public function test_trade_in_creation_failure_does_not_create_a_payment_or_vehicle(): void
    {
        $lot = $this->createLot(10000);
        $brand = Brand::query()->firstOrFail();
        $license = 'RF'.random_int(1000, 9999);

        GeneralState::query()->update(['name' => 'Estado temporario de teste']);

        $this->actingAs($this->admin())
            ->post(route('admin.vehicle-groups.payments.store', $lot), [
                'payment_type' => 'trade_in',
                'paid_at' => $this->paymentDate(),
                'amount' => 5000,
                'trade_in_license' => $license,
                'trade_in_brand_id' => $brand->id,
                'trade_in_model' => 'Falha esperada',
                'trade_in_year' => now()->year,
                'trade_in_kilometers' => 100,
            ])
            ->assertSessionHasErrors('trade_in_license');

        $this->assertSame(0, $lot->payments()->count());
        $this->assertFalse(Vehicle::query()->where('license', $license)->exists());
    }

    public function test_existing_client_trade_in_payment_flow_is_preserved(): void
    {
        $client = Client::query()->firstOrFail();
        $vehicle = Vehicle::query()->whereNotNull('license')->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.clients.payments.store', $client), [
                'payment_type' => 'trade_in',
                'paid_at' => $this->paymentDate(),
                'amount' => 3250,
                'trade_in_license' => $vehicle->license,
            ])
            ->assertRedirect(route('admin.clients.edit', $client));

        $this->assertTrue($client->payments()->where('amount', 3250)->exists());
        $this->assertTrue(VehicleTradeIn::query()
            ->where('created_vehicle_id', $vehicle->id)
            ->where('amount', 3250)
            ->exists());
    }

    private function createLot(float $total): VehicleGroup
    {
        return VehicleGroup::create([
            'customer_id' => Client::query()->firstOrFail()->id,
            'name' => 'Lote teste pagamentos '.uniqid(),
            'type' => 'lote',
            'wholesale_pvp' => $total,
            'total_amount' => $total,
            'distribution_mode' => 'global',
            'status' => 'open',
        ]);
    }

    private function admin()
    {
        return Role::query()->where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
    }

    private function paymentDate(): string
    {
        return now()->format(config('panel.date_format'));
    }
}
