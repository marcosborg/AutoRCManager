<?php

namespace Tests\Feature;

use App\Models\{Brand, Client, Permission, Role, User, Vehicle, VehicleTradeIn};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TradeInDeliveryDeclarationTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access'];

    private function actor(array $permissions = self::PERMISSIONS): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Trade-in delivery ')]);
        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        }
        $user->roles()->attach($role);
        return $user;
    }

    private function tradeIn(array $attributes = []): VehicleTradeIn
    {
        return VehicleTradeIn::create($attributes + [
            'sold_vehicle_id' => Vehicle::create(['license' => 'ZZ-98-YY'])->id,
            'license' => 'AA-12-BB', 'normalized_license' => 'AA12BB',
            'amount' => 5000, 'status' => VehicleTradeIn::STATUS_PENDING,
        ]);
    }

    private function url(string $action, VehicleTradeIn $tradeIn): string
    {
        return route('admin.vehicle-trade-ins.delivery.'.$action, $tradeIn);
    }

    private function data(): array
    {
        return ['name' => 'Proprietário de exemplo', 'license' => 'AA-12-BB',
            'kilometers' => 145000, 'delivery_date' => '2026-11-06', 'faults' => 'Risco na porta.'];
    }

    public function test_prefill_uses_received_vehicle_and_selling_customer_without_changing_records(): void
    {
        $this->actingAs($this->actor());
        $client = Client::create(['name' => 'Proprietário correto', 'vat' => '123456789',
            'address' => 'Rua do Proprietário', 'zip' => '4000-123', 'location' => 'Porto']);
        $soldBrand = Brand::create(['name' => 'Marca vendida']);
        $receivedBrand = Brand::create(['name' => 'Marca recebida']);
        $sold = Vehicle::create(['license' => 'ZZ-98-YY', 'brand_id' => $soldBrand->id,
            'model' => 'Modelo vendido', 'kilometers' => 12345, 'client_id' => $client->id]);
        $received = Vehicle::create(['license' => 'AA-12-BB', 'brand_id' => $receivedBrand->id,
            'model' => 'Modelo recebido', 'kilometers' => 145000]);
        $tradeIn = $this->tradeIn(['sold_vehicle_id' => $sold->id, 'created_vehicle_id' => $received->id]);
        $models = [$client, $sold, $received, $tradeIn];
        $before = array_map(fn ($model) => $model->fresh()->getAttributes(), $models);
        $paymentsBefore = $sold->client_payments()->count();

        $form = $this->get($this->url('edit', $tradeIn))->assertOk()
            ->assertSee('Proprietário correto')->assertSee('Marca recebida')->assertSee('Modelo recebido')
            ->assertSee('Rever declaração de entrega')->assertSee('name="faults"', false)
            ->assertSee('name="delivery_date"', false);
        $data = $form->viewData('data');
        $this->assertSame('Proprietário correto', $data['name']);
        $this->assertSame('123456789', $data['vat']);
        $this->assertSame('Rua do Proprietário', $data['address']);
        $this->assertSame('AA-12-BB', $data['license']);
        $this->assertSame('Marca recebida', $data['brand']);
        $this->assertSame('Modelo recebido', $data['model']);
        $this->assertEquals(145000, $data['kilometers']);
        $this->assertStringContainsString('no-store', $form->headers->get('Cache-Control'));

        $this->post($this->url('prepare', $tradeIn), $this->data())->assertSessionHasNoErrors()
            ->assertRedirect($this->url('preview', $tradeIn));
        $preview = $this->get($this->url('preview', $tradeIn))->assertOk()
            ->assertSee('a título de retoma')->assertDontSee('consignação')->assertSee('Risco na porta.')
            ->assertSee('Campos por preencher:');
        $pdf = $this->get($this->url('pdf', $tradeIn))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('no-store', $pdf->headers->get('Cache-Control'));
        foreach ($models as $index => $model) {
            $this->assertSame($before[$index], $model->fresh()->getAttributes());
        }
        $this->assertSame($paymentsBefore, $sold->client_payments()->count());
        foreach ([$sold, $received, $tradeIn] as $model) $this->assertSame(0, $model->media()->count());
        $html = view('admin.vehicleTradeIns.delivery-print', ['tradeIn' => $tradeIn,
            'data' => $preview->viewData('data'), 'pdf' => true])->render();
        $this->assertStringNotContainsString('Campos por preencher:', $html);
        $this->assertStringNotContainsString('Alterar dados', $html);
        $this->assertStringContainsString('O Proprietário', $html);
        $this->assertStringContainsString('O Stand', $html);
    }

    public function test_optional_fields_and_unconverted_retomas_are_supported(): void
    {
        $this->actingAs($this->actor());
        $tradeIn = $this->tradeIn();
        $form = $this->get($this->url('edit', $tradeIn))->assertOk();
        $this->assertNull($form->viewData('data')['name']);
        $this->assertNull($form->viewData('data')['brand']);
        $this->post($this->url('prepare', $tradeIn), ['name' => 'Cliente', 'license' => 'AA-12-BB'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->get($this->url('preview', $tradeIn))->assertOk()->assertSee('_______ de ____________________ de __________');
        $this->get($this->url('pdf', $tradeIn))->assertOk();
    }

    public function test_validation_and_html_escaping(): void
    {
        $this->actingAs($this->actor());
        $tradeIn = $this->tradeIn();
        foreach ([['name' => ''], ['license' => ''], ['name' => str_repeat('a', 151)],
            ['kilometers' => -1], ['kilometers' => 10000000], ['kilometers' => '12.5'],
            ['delivery_date' => '2026-02-30'], ['faults' => str_repeat('a', 1201)]] as $bad) {
            $this->postJson($this->url('prepare', $tradeIn), array_merge($this->data(), $bad))->assertUnprocessable();
        }
        $this->post($this->url('prepare', $tradeIn), array_merge($this->data(), [
            'name' => '<script>alert(1)</script>', 'faults' => '<img src=x onerror=alert(2)>',
        ]))->assertSessionHasNoErrors();
        $this->get($this->url('preview', $tradeIn))->assertOk()->assertSee('&lt;script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(2)&gt;', false)->assertDontSee('<script>alert', false);
    }

    public function test_drafts_are_isolated_by_retoma_and_user(): void
    {
        $this->actingAs($this->actor());
        $tradeIn = $this->tradeIn();
        $other = $this->tradeIn(['license' => 'CC-34-DD', 'normalized_license' => 'CC34DD']);
        $this->post($this->url('prepare', $tradeIn), $this->data())->assertSessionHasNoErrors();
        $this->get($this->url('preview', $tradeIn))->assertOk();
        foreach (['preview', 'pdf'] as $action) {
            $this->get($this->url($action, $other))->assertRedirect($this->url('edit', $other));
        }
        $this->actingAs($this->actor());
        foreach (['preview', 'pdf'] as $action) {
            $this->get($this->url($action, $tradeIn))->assertRedirect($this->url('edit', $tradeIn));
        }
    }

    public function test_every_document_permission_is_required_for_all_actions(): void
    {
        $tradeIn = $this->tradeIn();
        foreach (self::PERMISSIONS as $missing) {
            $this->actingAs($this->actor(array_values(array_diff(self::PERMISSIONS, [$missing]))));
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get($this->url($action, $tradeIn))->assertForbidden();
            $this->post($this->url('prepare', $tradeIn), $this->data())->assertForbidden();
        }
    }

    public function test_rejected_and_deleted_retomas_cannot_generate_documents(): void
    {
        $this->actingAs($this->actor());
        $rejected = $this->tradeIn(['status' => VehicleTradeIn::STATUS_REJECTED]);
        $deleted = $this->tradeIn();
        $deleted->delete();
        $standalone = $this->tradeIn(['sold_vehicle_id' => null]);
        foreach ([$rejected, $deleted, $standalone] as $tradeIn) {
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get($this->url($action, $tradeIn))->assertNotFound();
            $this->post($this->url('prepare', $tradeIn), $this->data())->assertNotFound();
        }
    }

    public function test_preparation_button_only_appears_for_authorized_sale_linked_retomas(): void
    {
        $this->actingAs($this->actor());
        $valid = $this->tradeIn();
        $this->get($this->url('edit', $valid))->assertOk();
        $html = view('admin.vehicleTradeIns.delivery-button', ['tradeIn' => $valid])->render();
        $this->assertStringContainsString('Preparar declaração de entrega', $html);
        $this->assertStringContainsString($this->url('edit', $valid), $html);
        foreach ([$this->tradeIn(['sold_vehicle_id' => null]),
            $this->tradeIn(['status' => VehicleTradeIn::STATUS_REJECTED])] as $tradeIn) {
            $this->assertStringNotContainsString('Preparar declaração de entrega',
                view('admin.vehicleTradeIns.delivery-button', compact('tradeIn'))->render());
        }
        $this->actingAs($this->actor([]));
        $this->get($this->url('edit', $valid))->assertForbidden();
        $this->assertStringNotContainsString('Preparar declaração de entrega',
            view('admin.vehicleTradeIns.delivery-button', ['tradeIn' => $valid])->render());
    }

    public function test_sale_preparation_links_identify_each_eligible_trade_in(): void
    {
        $this->actingAs($this->actor());
        $first = $this->tradeIn();
        $second = $this->tradeIn(['sold_vehicle_id' => $first->sold_vehicle_id, 'license' => 'CC-34-DD']);
        $rejected = $this->tradeIn(['sold_vehicle_id' => $first->sold_vehicle_id, 'status' => VehicleTradeIn::STATUS_REJECTED]);
        $html = view('admin.vehicleTradeIns.delivery-sale-links', ['vehicle' => $first->sold_vehicle])->render();
        $this->assertStringContainsString($this->url('edit', $first), $html);
        $this->assertStringContainsString($this->url('edit', $second), $html);
        $this->assertStringContainsString('CC-34-DD', $html);
        $this->assertStringNotContainsString($this->url('edit', $rejected), $html);
        $this->actingAs($this->actor([]));
        $this->assertStringNotContainsString('Preparar declaração', view('admin.vehicleTradeIns.delivery-sale-links', ['vehicle' => $first->sold_vehicle])->render());
    }
}
