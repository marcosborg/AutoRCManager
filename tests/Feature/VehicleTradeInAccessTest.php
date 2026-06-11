<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\VehicleTradeIn;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleTradeInAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stand_can_view_converted_trade_ins_only(): void
    {
        $user = Role::where('title', 'Stand')->firstOrFail()->users()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_CONVERTED]))
            ->assertOk()
            ->assertSee('Adicionar retoma')
            ->assertDontSee('Dar como verificado');

        $this->actingAs($user)
            ->get(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_PENDING]))
            ->assertForbidden();
    }

    public function test_stand_adm_keeps_full_trade_in_access(): void
    {
        $user = Role::where('title', 'Stand Adm')->firstOrFail()->users()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_CONVERTED]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_PENDING]))
            ->assertOk();
    }

    public function test_stand_can_create_a_trade_in_without_a_sold_vehicle(): void
    {
        Storage::fake('public');

        $user = Role::where('title', 'Stand')->firstOrFail()->users()->firstOrFail();
        $brand = Brand::query()->firstOrFail();
        $license = 'TI-' . random_int(1000, 9999);

        $this->actingAs($user)
            ->get(route('admin.vehicle-trade-ins.create'))
            ->assertOk()
            ->assertSee('Adicionar retoma sem venda associada');

        $this->actingAs($user)
            ->post(route('admin.vehicle-trade-ins.store'), [
                'trade_in_license' => $license,
                'trade_in_amount' => 12500,
                'trade_in_brand_id' => $brand->id,
                'trade_in_model' => 'Modelo teste',
                'trade_in_year' => now()->year,
                'trade_in_kilometers' => 45000,
                'trade_in_notes' => 'Criada pelo teste de retoma autonoma.',
                'has_vehicle_delivery_declaration' => 1,
                'vehicle_delivery_declaration' => [UploadedFile::fake()->create('entrega-viatura.pdf', 100, 'application/pdf')],
                'internal_invoice' => [UploadedFile::fake()->create('fatura.pdf', 100, 'application/pdf')],
                'inicial' => [UploadedFile::fake()->image('viatura.jpg')],
            ])
            ->assertRedirect(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_CONVERTED]));

        $tradeIn = VehicleTradeIn::where('license', $license)->firstOrFail();

        $this->assertNull($tradeIn->sold_vehicle_id);
        $this->assertTrue($tradeIn->has_vehicle_delivery_declaration);
        $this->assertCount(1, $tradeIn->getMedia('vehicle_delivery_declaration'));
        $this->assertSame(VehicleTradeIn::STATUS_PENDING, $tradeIn->status);
        $this->assertNotNull($tradeIn->created_vehicle_id);
        $this->assertSame($license, Vehicle::findOrFail($tradeIn->created_vehicle_id)->license);

        $standAdm = Role::where('title', 'Stand Adm')->firstOrFail()->users()->firstOrFail();

        $this->actingAs($standAdm)
            ->post(route('admin.vehicle-trade-ins.convert', $tradeIn))
            ->assertRedirect(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_PENDING]));

        $this->assertSame(VehicleTradeIn::STATUS_CONVERTED, $tradeIn->fresh()->status);
    }
}
