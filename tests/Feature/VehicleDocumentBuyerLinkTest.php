<?php

namespace Tests\Feature;

use App\Models\{Client, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleDocumentBuyerLinkTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(bool $canEditClient): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Document buyer ')]);
        $permissions = ['vehicle_edit', 'vehicle_documents_area_access', 'vehicle_others_area_access'];
        if ($canEditClient) $permissions[] = 'client_edit';
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user->roles()->attach($role);
        return $user;
    }

    public function test_buyer_link_opens_separately_and_does_not_modify_records_or_draft(): void
    {
        $user = $this->actor(true);
        $this->actingAs($user);
        $client = Client::create(['name' => 'Comprador exemplo']);
        $vehicle = Vehicle::create(['license' => 'BUYER-LINK', 'client_id' => $client->id]);
        $before = [$client->fresh()->getAttributes(), $vehicle->fresh()->getAttributes()];
        foreach (['sale', 'warranty'] as $document) {
            $key = $document.'_declaration_v1.'.$user->id.'.'.$vehicle->id;
            $draft = ['name' => 'Nome em preparação'];
            $response = $this->withSession([$key => $draft])->get(route('admin.vehicles.documents.'.$document.'.edit', $vehicle))->assertOk();
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $links = (new \DOMXPath($dom))->query('//a[@data-document-buyer-link]');
            $this->assertSame(1, $links->length);
            $this->assertSame(route('admin.clients.edit', $client), $links->item(0)->getAttribute('href'));
            $this->assertSame('_blank', $links->item(0)->getAttribute('target'));
            $this->assertSame('noopener noreferrer', $links->item(0)->getAttribute('rel'));
            $response->assertSee('Nome em preparação')->assertSee('não atualizam automaticamente');
            $this->assertSame($draft, session($key));
        }
        $this->assertSame($before, [$client->fresh()->getAttributes(), $vehicle->fresh()->getAttributes()]);
    }

    public function test_link_requires_client_edit_and_an_existing_active_buyer(): void
    {
        $client = Client::create(['name' => 'Comprador exemplo']);
        $vehicle = Vehicle::create(['license' => 'BUYER-LINK', 'client_id' => $client->id]);
        $this->actingAs($this->actor(false));
        foreach (['sale', 'warranty'] as $document) {
            $this->get(route('admin.vehicles.documents.'.$document.'.edit', $vehicle))->assertOk()->assertDontSee('data-document-buyer-link', false);
        }
        $this->actingAs($this->actor(true));
        $client->delete();
        foreach (['sale', 'warranty'] as $document) {
            $this->get(route('admin.vehicles.documents.'.$document.'.edit', $vehicle))->assertOk()->assertDontSee('data-document-buyer-link', false);
        }
        $vehicle->update(['client_id' => null]);
        foreach (['sale', 'warranty'] as $document) {
            $this->get(route('admin.vehicles.documents.'.$document.'.edit', $vehicle))->assertOk()->assertDontSee('data-document-buyer-link', false);
        }
    }
}
