<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\GeneralState;
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
        $license = 'TI' . random_int(1000, 9999);
        $formattedLicense = substr($license, 0, 2) . '-' . substr($license, 2, 2) . '-' . substr($license, 4, 2);

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
            ])
            ->assertSessionHasErrors(['trade_in_iuc_month'], null, 'trade_in');

        $this->actingAs($user)
            ->post(route('admin.vehicle-trade-ins.store'), [
                'trade_in_license' => $license,
                'trade_in_amount' => 12500,
                'trade_in_brand_id' => $brand->id,
                'trade_in_model' => 'Modelo teste',
                'trade_in_year' => now()->year,
                'trade_in_kilometers' => 45000,
                'trade_in_iuc_month' => 'Julho',
                'trade_in_iuc_value' => 147.35,
                'trade_in_notes' => 'Criada pelo teste de retoma autonoma.',
                'has_vehicle_delivery_declaration' => 1,
                'vehicle_delivery_declaration' => [UploadedFile::fake()->create('entrega-viatura.pdf', 100, 'application/pdf')],
                'internal_invoice' => [UploadedFile::fake()->create('fatura.pdf', 100, 'application/pdf')],
                'inicial' => [UploadedFile::fake()->image('viatura.jpg')],
            ])
            ->assertRedirect(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_CONVERTED]));

        $tradeIn = VehicleTradeIn::where('license', $formattedLicense)->firstOrFail();

        $this->assertNull($tradeIn->sold_vehicle_id);
        $this->assertTrue($tradeIn->has_vehicle_delivery_declaration);
        $this->assertCount(1, $tradeIn->getMedia('vehicle_delivery_declaration'));
        $this->assertSame(VehicleTradeIn::STATUS_PENDING, $tradeIn->status);
        $this->assertNotNull($tradeIn->created_vehicle_id);
        $createdVehicle = Vehicle::findOrFail($tradeIn->created_vehicle_id);
        $this->assertSame($formattedLicense, $createdVehicle->license);
        $this->assertSame('Julho', $createdVehicle->mes_iuc);
        $this->assertSame(147.35, (float) $createdVehicle->iuc_price);

        $standAdm = Role::where('title', 'Stand Adm')->firstOrFail()->users()->firstOrFail();

        $this->actingAs($standAdm)
            ->post(route('admin.vehicle-trade-ins.convert', $tradeIn))
            ->assertRedirect(route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_PENDING]));

        $this->assertSame(VehicleTradeIn::STATUS_CONVERTED, $tradeIn->fresh()->status);
    }

    public function test_admin_and_adm_dashboard_show_current_unpaid_iuc_alert_with_optional_value(): void
    {
        $month = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ][(int) now()->format('n')];
        $license = 'IUC-'.random_int(1000, 9999);
        $vehicle = Vehicle::query()->create([
            'license' => $license,
            'general_state_id' => GeneralState::query()->firstOrFail()->id,
            'mes_iuc' => $month,
            'iuc_price' => 83.60,
        ]);

        foreach (['Admin', 'Adm'] as $roleTitle) {
            $user = Role::where('title', $roleTitle)->firstOrFail()->users()->firstOrFail();

            $this->actingAs($user)
                ->get(route('admin.home'))
                ->assertOk()
                ->assertSee('IUC a pagamento em '.$month)
                ->assertSee($license)
                ->assertSee('83,60 EUR');
        }

        $stand = Role::where('title', 'Stand')->firstOrFail()->users()->firstOrFail();
        $this->actingAs($stand)
            ->get(route('admin.home'))
            ->assertOk()
            ->assertDontSee('IUC a pagamento em '.$month);

        $vehicle->update(['iuc_paid_date' => now()->format(config('panel.date_format'))]);
        $admin = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.home'))
            ->assertOk()
            ->assertDontSee($license);
    }
}
