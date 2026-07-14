<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_global_search_finds_vehicles_by_normalized_license(): void
    {
        $user = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $vehicle = Vehicle::create([
            'license' => 'GX72QZ',
            'model' => 'Modelo Pesquisa Global',
        ]);

        $this->actingAs($user)
            ->getJson(route('admin.globalSearch', [
                'search' => ['term' => 'GX72QZ'],
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'vehicle-'.$vehicle->id,
                'model' => 'Viatura',
                'title' => 'GX-72-QZ',
                'url' => route('admin.vehicles.edit', $vehicle),
            ]);
    }

    public function test_global_search_finds_clients_by_identity_and_contact_fields(): void
    {
        $user = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $client = Client::create([
            'name' => 'Cliente Atlas Pesquisa',
            'vat' => '509987654',
            'phone' => '919 876 543',
            'email' => 'atlas-pesquisa@example.test',
        ]);

        foreach (['Atlas Pesquisa', '509987654', '919876', 'atlas-pesquisa@example.test'] as $term) {
            $this->actingAs($user)
                ->getJson(route('admin.globalSearch', [
                    'search' => ['term' => $term],
                ]))
                ->assertOk()
                ->assertJsonFragment([
                    'id' => 'client-'.$client->id,
                    'model' => 'Cliente',
                    'title' => 'Cliente Atlas Pesquisa',
                    'url' => route('admin.clients.edit', $client),
                ]);
        }
    }

    public function test_global_search_requires_at_least_three_characters(): void
    {
        $user = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('admin.globalSearch', [
                'search' => ['term' => 'GX'],
            ]))
            ->assertOk()
            ->assertExactJson(['results' => []]);
    }

    public function test_global_search_hides_models_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('admin.globalSearch', [
                'search' => ['term' => 'Oficina'],
            ]))
            ->assertOk()
            ->assertExactJson(['results' => []]);
    }
}
