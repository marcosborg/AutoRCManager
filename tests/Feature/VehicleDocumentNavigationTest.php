<?php

namespace Tests\Feature;

use App\Models\{Permission, Role, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleDocumentNavigationTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions, ?string $roleTitle = null): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['title' => $roleTitle ?? uniqid('Document menu ')]);
        foreach ($permissions as $title) {
            $role->permissions()->syncWithoutDetaching([Permission::firstOrCreate(['title' => $title])->id]);
        }
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function documentationMenu(string $html): \DOMNodeList
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        return (new \DOMXPath($dom))->query('//aside//a[@href="'.route('admin.vehicles.documents.review').'"]');
    }

    public function test_authorized_users_have_a_direct_documentation_menu_with_active_state(): void
    {
        $this->actingAs($this->actor(['vehicle_access', 'vehicle_documents_area_access']));
        $response = $this->get(route('admin.vehicles.documents.review'))->assertOk();
        $links = $this->documentationMenu($response->getContent());
        $this->assertCount(1, $links);
        $this->assertStringContainsString('Documenta', $links->item(0)->textContent);
        $this->assertSame('active', $links->item(0)->parentNode->getAttribute('class'));
        $response->assertDontSee('Abrir documentos');
    }

    public function test_menu_does_not_grant_missing_permissions_or_workshop_access(): void
    {
        foreach ([[], ['vehicle_access'], ['vehicle_documents_area_access']] as $permissions) {
            $this->actingAs($this->actor($permissions));
            $response = $this->get(route('admin.home'))->assertOk();
            $this->assertCount(0, $this->documentationMenu($response->getContent()));
            $this->get(route('admin.vehicles.documents.review'))->assertForbidden();
        }

        foreach (['Aux. oficina', 'Chefe oficina'] as $role) {
            $this->actingAs($this->actor(['vehicle_access', 'vehicle_documents_area_access'], $role));
            $response = $this->get(route('admin.home'))->assertOk();
            $this->assertCount(0, $this->documentationMenu($response->getContent()));
            $this->get(route('admin.vehicles.documents.review'))->assertForbidden();
        }
    }
}
