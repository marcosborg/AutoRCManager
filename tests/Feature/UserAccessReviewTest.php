<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserAccessReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(string $title, array $abilities): User
    {
        $role = Role::firstOrCreate(['title' => $title]);
        $role->permissions()->sync(collect($abilities)->map(fn ($ability) => Permission::firstOrCreate(['title' => $ability])->id));
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        return $user;
    }

    public function test_summary_uses_target_permissions_and_does_not_change_them(): void
    {
        $viewer = $this->actor('Review viewer '.uniqid(), ['user_show', 'vehicle_edit']);
        $target = $this->actor('Review target '.uniqid(), ['vehicle_access']);
        $rolesBefore = $target->roles()->pluck('roles.id')->all();
        $response = $this->actingAs($viewer)->get(route('admin.users.show', $target));
        $response->assertOk()->assertSee('Resumo dos principais acessos');
        $review = $response->viewData('accessReview');
        $this->assertTrue($review['vehicle_access']['allowed']);
        $this->assertFalse($review['vehicle_edit']['allowed']);
        $this->assertSame($rolesBefore, $target->roles()->pluck('roles.id')->all());
    }

    public function test_summary_respects_workshop_and_marketing_restrictions(): void
    {
        $viewer = $this->actor('Review viewer '.uniqid(), ['user_show']);
        foreach (['Aux. oficina', 'Marketing Stand'] as $role) {
            $target = $this->actor($role, ['lead_edit', 'lead_delete']);
            $response = $this->actingAs($viewer)->get(route('admin.users.show', $target));
            $response->assertOk();
            $review = $response->viewData('accessReview');
            $this->assertFalse($review['lead_edit']['allowed']);
            $this->assertFalse($review['lead_delete']['allowed']);
        }
    }

    public function test_summary_requires_user_view_permission(): void
    {
        $viewer = $this->actor('No review '.uniqid(), []);
        $this->actingAs($viewer)->get(route('admin.users.show', User::factory()->create()))->assertForbidden();
    }
}
