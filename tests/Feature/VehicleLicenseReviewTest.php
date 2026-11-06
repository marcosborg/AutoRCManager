<?php

namespace Tests\Feature;

use App\Models\{Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleLicenseReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions = ['vehicle_access', 'vehicle_edit']): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('License review ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    public function test_review_is_read_only_and_separates_foreign_registrations(): void
    {
        $this->actingAs($this->actor());
        $bad = Vehicle::create(['license' => 'TEST-REVIEW', 'model' => 'Review marker']);
        $missing = Vehicle::create(['license' => null]);
        $foreign = Vehicle::create(['license' => 'FOREIGN', 'foreign_license' => 'AB123CD']);
        $valid = collect(['AB-12-34', '12-34-AB', '12-AB-34', 'AB-12-CD', 'ab 12 cd', '12.34.AB'])->map(fn ($license) => Vehicle::create(compact('license')));
        $deleted = Vehicle::create(['license' => 'DELETED']);
        $deleted->delete();
        $before = $bad->fresh()->getAttributes();
        $ids = [];
        $page = 1;
        do {
            $response = $this->get(route('admin.vehicles.licenses.review', ['page' => $page++]))->assertOk();
            $results = $response->viewData('vehicles');
            $ids = array_merge($ids, $results->pluck('id')->all());
        } while ($results->hasMorePages());
        $this->assertContains($bad->id, $ids);
        $this->assertContains($missing->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($deleted->id, $ids);
        foreach ($valid as $vehicle) $this->assertNotContains($vehicle->id, $ids);
        $this->assertSame($before, $bad->fresh()->getAttributes());
        $this->get(route('admin.vehicles.licenses.review', ['reason' => 'foreign', 'license' => 'AB123CD']))->assertOk()->assertSee('AB123CD')->assertDontSee('Review marker');
        $this->get(route('admin.vehicles.licenses.review', ['reason' => 'format', 'license' => 'TEST-REVIEW']))->assertOk()->assertSee('Review marker')->assertSee(route('admin.vehicles.edit', $bad), false);
    }

    public function test_filters_validation_and_permissions(): void
    {
        $this->actingAs($this->actor());
        $company = array_key_first(Vehicle::DESTINATION_COMPANIES);
        $vehicle = Vehicle::create(['license' => 'REVIEW-UNIQUE', 'destination_company' => $company]);
        $this->get(route('admin.vehicles.licenses.review', ['license' => 'REVIEW-UNIQUE', 'destination_company' => $company]))->assertOk()->assertSee('REVIEW-UNIQUE');
        $this->get(route('admin.vehicles.licenses.review', ['license' => 'REVIEW-UNIQUE', 'destination_company' => 'unassigned']))->assertOk()->assertDontSee('Abrir ficha');
        $this->getJson(route('admin.vehicles.licenses.review', ['reason' => 'unsafe']))->assertUnprocessable();
        foreach ([[], ['vehicle_access'], ['vehicle_edit']] as $permissions) {
            $this->actingAs($this->actor($permissions));
            $this->get(route('admin.vehicles.licenses.review'))->assertForbidden();
        }
    }

    public function test_foreign_confirmation_preserves_original_and_is_idempotent(): void
    {
        $this->actingAs($this->actor());
        $vehicle = Vehicle::create(['license' => 'GM-559-KX']);
        $url = route('admin.vehicles.licenses.foreign', $vehicle);
        $data = ['expected_license' => $vehicle->license, 'confirmed' => '1'];
        $this->post($url, ['expected_license' => $vehicle->license])->assertSessionHasErrors('confirmed');
        $this->assertNull($vehicle->fresh()->foreign_license);
        $this->post($url, $data)->assertRedirect(route('admin.vehicles.licenses.review'));
        $this->assertSame('GM-559-KX', $vehicle->fresh()->license);
        $this->assertSame('GM-559-KX', $vehicle->fresh()->foreign_license);
        $this->post($url, $data)->assertRedirect();
        $this->assertSame(1, \App\Models\AuditLog::where('description', 'vehicle_license:confirmed_foreign')->where('subject_id', $vehicle->id)->count());
        $review = $this->get(route('admin.vehicles.licenses.review', ['license' => 'GM559KX']))->assertOk();
        $this->assertNotContains($vehicle->id, $review->viewData('vehicles')->pluck('id')->all());
        $this->get(route('admin.vehicles.licenses.review', ['reason' => 'foreign', 'license' => 'GM559KX']))->assertSee('GM-559-KX');
    }

    public function test_foreign_confirmation_rejects_stale_data_conflicts_and_missing_permission(): void
    {
        $this->actingAs($this->actor());
        $vehicle = Vehicle::create(['license' => 'GM-559-KX', 'foreign_license' => 'OTHER']);
        $url = route('admin.vehicles.licenses.foreign', $vehicle);
        $this->post($url, ['expected_license' => 'STALE', 'confirmed' => 1])->assertStatus(409);
        $this->post($url, ['expected_license' => $vehicle->license, 'confirmed' => 1])->assertStatus(409);
        $this->assertSame('OTHER', $vehicle->fresh()->foreign_license);
        $this->actingAs($this->actor(['vehicle_access']));
        $this->post($url, ['expected_license' => $vehicle->license, 'confirmed' => 1])->assertForbidden();
    }
}
