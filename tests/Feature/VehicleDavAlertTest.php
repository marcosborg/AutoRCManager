<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\Role;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VehicleDavAlertTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dav_upload_sets_timestamp_and_dashboard_alert_expires_after_seven_days(): void
    {
        $admin = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $state = GeneralState::firstOrFail();
        $vehicle = Vehicle::create([
            'general_state_id' => $state->id,
            'license' => 'DV12AL',
            'model' => 'DAV recente',
        ]);
        $expiredVehicle = Vehicle::create([
            'general_state_id' => $state->id,
            'license' => 'DV99EX',
            'model' => 'DAV expirada',
            'dav_created_at' => now()->subDays(8),
        ]);

        $upload = $this->actingAs($admin)->postJson(route('admin.vehicles.storeMedia'), [
            'file' => UploadedFile::fake()->create('dav.pdf', 20, 'application/pdf'),
            'size' => 10,
        ])->assertOk();

        $this->actingAs($admin)->put(route('admin.vehicles.update', $vehicle), [
            'general_state_id' => $state->id,
            'model' => $vehicle->model,
            'dav' => [$upload->json('name')],
        ])->assertRedirect();

        $vehicle->refresh();
        $this->assertNotNull($vehicle->dav_created_at);
        $this->assertCount(1, $vehicle->dav);

        $this->actingAs($admin)->get(route('admin.home'))
            ->assertOk()
            ->assertSee('DAV criada recentemente')
            ->assertSee('DV-12-AL')
            ->assertDontSee('DV-99-EX');
    }
}
