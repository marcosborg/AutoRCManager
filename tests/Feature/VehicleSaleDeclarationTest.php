<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\VehicleSaleDeclarationController;
use App\Models\{Client, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleSaleDeclarationTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(string $role = 'Declaration test', bool $allowed = true): User
    {
        $u = User::factory()->create();
        $r = Role::firstOrCreate(['title' => $role]);
        if ($allowed) foreach (['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access'] as $p) {
            $r->permissions()->syncWithoutDetaching(Permission::firstOrCreate(['title' => $p])->id);
        }
        $u->roles()->attach($r);
        return $u;
    }

    private function data(): array
    {
        return ['name' => 'Cliente de demonstração', 'vat' => '999999990', 'address' => 'Rua de exemplo, 1',
            'brand' => 'Marca teste', 'model' => 'Modelo teste', 'license' => 'TEST-01', 'year' => 2024,
            'total' => '20000,50', 'entry' => '0', 'deposit' => '500', 'finance' => '15000', 'debt' => '4500,50',
            'trade_value' => '0', 'guarantee' => 'yes', 'months' => 18, 'document_date' => '2026-11-06',
            'confirmed' => 1, 'company_confirmed' => 1];
    }

    public function test_only_an_unambiguous_active_trade_in_prefills_its_vehicle_fields(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $vehicle = Vehicle::create(['license' => 'DOC-SOLD']);
        $brand = \App\Models\Brand::create(['name' => 'Marca da retoma']);
        $received = Vehicle::create(['license' => 'AA-12-BB', 'brand_id' => $brand->id]);
        $trade = \App\Models\VehicleTradeIn::create(['sold_vehicle_id' => $vehicle->id,
            'created_vehicle_id' => $received->id, 'license' => $received->license, 'normalized_license' => 'AA12BB',
            'amount' => 5000, 'status' => \App\Models\VehicleTradeIn::STATUS_CONVERTED]);
        $before = [$vehicle->fresh()->getAttributes(), $trade->fresh()->getAttributes()];
        $url = route('admin.vehicles.documents.sale.edit', $vehicle);
        $data = $this->get($url)->assertOk()->viewData('data');
        $this->assertSame($trade->license, $data['trade_license']);
        $this->assertSame($brand->name, $data['trade_brand']);
        $this->assertArrayNotHasKey('trade_value', $data);
        $this->assertSame($before, [$vehicle->fresh()->getAttributes(), $trade->fresh()->getAttributes()]);

        $draft = ['trade_license' => 'ESCOLHA MANUAL', 'trade_brand' => 'Marca manual'];
        $key = 'sale_declaration_v1.'.$user->id.'.'.$vehicle->id;
        $this->withSession([$key => $draft])->get($url)->assertOk()->assertViewHas('data', $draft);
        session()->forget($key);
        $other = \App\Models\VehicleTradeIn::create(['sold_vehicle_id' => $vehicle->id,
            'license' => 'CC-34-DD', 'normalized_license' => 'CC34DD', 'amount' => 0,
            'status' => \App\Models\VehicleTradeIn::STATUS_PENDING]);
        $data = $this->get($url)->assertOk()->viewData('data');
        $this->assertNull($data['trade_license']);
        $this->assertNull($data['trade_brand']);
        $other->update(['status' => \App\Models\VehicleTradeIn::STATUS_REJECTED]);
        $this->assertSame($trade->license, $this->get($url)->assertOk()->viewData('data')['trade_license']);
    }

    public function test_document_preparation_links_are_in_the_correct_vehicle_tabs(): void
    {
        $user = $this->actor();
        $role = $user->roles->first();
        foreach (['vehicle_access', 'aquisition_of_the_vehicle'] as $permission) {
            $role->permissions()->syncWithoutDetaching(Permission::firstOrCreate(['title' => $permission])->id);
        }
        $this->actingAs($user);
        $vehicle = Vehicle::create(['license' => 'DOC-TABS']);
        $html = $this->get(route('admin.vehicles.edit', $vehicle))->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        foreach (['purchase' => 'vehicle-acquisition', 'sale' => 'vehicle-preparation-sale'] as $document => $tab) {
            $href = route('admin.vehicles.documents.'.$document.'.edit', $vehicle);
            $this->assertSame(1, $xpath->query('//div[@id="'.$tab.'"]//a[@href="'.$href.'"]')->length);
            $this->assertSame(0, $xpath->query('//div[@id="vehicle-documents"]//a[@href="'.$href.'"]')->length);
        }
    }

    public function test_prefill_preview_pdf_does_not_change_sale_or_customer(): void
    {
        $this->actingAs($this->actor());
        $client = Client::create(['name' => 'Comprador da venda', 'vat' => '999999990']);
        $v = Vehicle::create(['license' => 'DOC-01', 'client_id' => $client->id, 'pvp' => 25000]);
        $before = $v->fresh()->getAttributes();
        $clientBefore = $client->fresh()->getAttributes();
        $this->get(route('admin.vehicles.documents.sale.edit', $v))->assertOk()->assertSee('Comprador da venda')
            ->assertSee('Empresa vendedora:')->assertSee('O rascunho não fica guardado.')
            ->assertDontSee('Modelo enviado pela Dina')->assertDontSee('tal como recebido');
        $this->post(route('admin.vehicles.documents.sale.prepare', $v), $this->data())->assertRedirect(route('admin.vehicles.documents.sale.preview', $v));
        $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertOk()->assertSee('20.000,50')->assertSee('Geração Determinada')->assertSee('Cliente de demonstração');
        $pdf = $this->get(route('admin.vehicles.documents.sale.pdf', $v))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame($before, $v->fresh()->getAttributes());
        $this->assertSame($clientBefore, $client->fresh()->getAttributes());
        $this->assertSame(0, $v->media()->count());
        $this->assertSame(0, $v->documentFollowups()->count());
    }

    public function test_invalid_or_unconfirmed_values_cannot_be_printed(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'DOC-02']);
        foreach ([['deposit' => -1], ['company_confirmed' => 0], ['confirmed' => 0], ['guarantee' => 'no', 'months' => 12], ['guarantee' => 'yes', 'months' => null], ['total' => '1.234,56']] as $invalid) {
            $this->postJson(route('admin.vehicles.documents.sale.prepare', $v), array_merge($this->data(), $invalid))->assertUnprocessable();
            $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertRedirect();
        }
    }

    public function test_missing_optional_buyer_fields_are_warned_only_on_screen(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $v = Vehicle::create(['license' => 'DOC-CHECK']);
        $this->post(route('admin.vehicles.documents.sale.prepare', $v), $this->data())
            ->assertRedirect(route('admin.vehicles.documents.sale.preview', $v))->assertSessionHasNoErrors();
        $preview = $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertOk()
            ->assertSee('Dados do cliente por preencher:')->assertSee('BI/CC, Código postal, Localidade')
            ->assertSee('Pode continuar, mas estes campos ficarão em branco no documento.')
            ->assertSee('Descarregar PDF');
        $data = session('sale_declaration_v1.'.$user->id.'.'.$v->id);
        $pdfHtml = view('admin.vehicles.sale-declaration-print', [
            'vehicle' => $v, 'data' => $data, 'pdf' => true,
            'missingBuyerFields' => ['identity' => 'BI/CC', 'zip' => 'Código postal', 'location' => 'Localidade'],
        ])->render();
        $this->assertStringNotContainsString('Dados do cliente por preencher:', $pdfHtml);
        $this->get(route('admin.vehicles.documents.sale.pdf', $v))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $complete = array_merge($this->data(), ['identity' => 'TESTE123', 'zip' => '1000-001', 'location' => 'Lisboa']);
        $this->post(route('admin.vehicles.documents.sale.prepare', $v), $complete)->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertOk()->assertDontSee('Dados do cliente por preencher:');
    }

    public function test_amounts_can_be_omitted_or_left_blank_without_printing_zero(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        $v = Vehicle::create(['license' => 'DOC-OPTIONAL']);
        $before = $v->fresh()->getAttributes();
        $amountKeys = array_keys(VehicleSaleDeclarationController::AMOUNTS);
        $base = array_diff_key($this->data(), array_flip($amountKeys));
        $form = $this->get(route('admin.vehicles.documents.sale.edit', $v))->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML($form);
        foreach ($amountKeys as $key) {
            $input = $dom->getElementById('sale-'.$key);
            $this->assertNotNull($input);
            $this->assertFalse($input->hasAttribute('required'), $key.' must be optional in the form');
        }

        foreach ([[], array_fill_keys($amountKeys, ''), array_fill_keys($amountKeys, null)] as $amounts) {
            $this->post(route('admin.vehicles.documents.sale.prepare', $v), $base + $amounts)
                ->assertRedirect(route('admin.vehicles.documents.sale.preview', $v))->assertSessionHasNoErrors();
            $data = session('sale_declaration_v1.'.$user->id.'.'.$v->id);
            foreach ($amountKeys as $key) {
                $this->assertArrayHasKey($key, $data);
                $this->assertNull($data[$key]);
            }
            $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertOk()->assertDontSee('0,00');
            $printHtml = view('admin.vehicles.sale-declaration-print', ['vehicle' => $v, 'data' => $data, 'pdf' => true])->render();
            $this->assertStringNotContainsString('0,00', $printHtml);
            foreach (VehicleSaleDeclarationController::AMOUNTS as $key => $label) {
                $this->assertStringContainsString($key === 'trade_value' ? 'Retoma:' : $label, $printHtml);
            }
            $pdf = $this->get(route('admin.vehicles.documents.sale.pdf', $v))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        }
        $this->assertSame($before, $v->fresh()->getAttributes());
        $this->assertSame(0, $v->media()->count());
        $this->assertSame(0, $v->documentFollowups()->count());
    }

    public function test_explicit_zero_remains_distinct_from_an_empty_amount(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'DOC-ZERO']);
        $amounts = array_fill_keys(array_keys(VehicleSaleDeclarationController::AMOUNTS), '0');
        $this->post(route('admin.vehicles.documents.sale.prepare', $v), array_merge($this->data(), $amounts))
            ->assertRedirect(route('admin.vehicles.documents.sale.preview', $v))->assertSessionHasNoErrors();
        $html = $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertOk()->getContent();
        $this->assertSame(count($amounts), substr_count($html, '0,00'));
    }

    public function test_optional_amounts_still_reject_invalid_supplied_values(): void
    {
        $this->actingAs($this->actor());
        $v = Vehicle::create(['license' => 'DOC-INVALID']);
        foreach (array_keys(VehicleSaleDeclarationController::AMOUNTS) as $key) {
            foreach (['-1', 'abc', '1,234', '100000000'] as $invalid) {
                $this->postJson(route('admin.vehicles.documents.sale.prepare', $v), array_merge($this->data(), [$key => $invalid]))
                    ->assertUnprocessable()->assertJsonValidationErrors($key);
            }
        }
    }

    public function test_permissions_session_isolation_and_html_escaping(): void
    {
        $v = Vehicle::create(['license' => 'DOC-03']);
        foreach (['Aux. oficina', 'Chefe oficina', 'No declaration access'] as $role) {
            $this->actingAs($this->actor($role, $role !== 'No declaration access'));
            foreach (['edit', 'preview', 'pdf'] as $action) $this->get(route('admin.vehicles.documents.sale.'.$action, $v))->assertForbidden();
            $this->post(route('admin.vehicles.documents.sale.prepare', $v), $this->data())->assertForbidden();
        }
        $this->actingAs($this->actor());
        $this->post(route('admin.vehicles.documents.sale.prepare', $v), array_merge($this->data(), ['name' => '<script>alert(1)</script>']))->assertRedirect();
        $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertDontSee('<script>alert(1)</script>', false);
        $other = Vehicle::create(['license' => 'DOC-04']);
        $this->get(route('admin.vehicles.documents.sale.preview', $other))->assertRedirect();
        $this->actingAs($this->actor());
        $this->get(route('admin.vehicles.documents.sale.preview', $v))->assertRedirect();
    }
}
