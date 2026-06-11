<?php

namespace Tests\Feature;

use App\Models\Provenience;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClientProvenienceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_provenience_is_required_when_creating_a_client(): void
    {
        $user = Role::where('title', 'Stand')->firstOrFail()->users()->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.clients.store'), ['name' => 'Cliente sem proveniência'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('provenience_id');
    }

    public function test_client_can_be_created_with_a_provenience(): void
    {
        $user = Role::where('title', 'Stand')->firstOrFail()->users()->firstOrFail();
        $provenience = Provenience::where('active', true)->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.clients.store'), [
                'name' => 'Cliente com proveniência',
                'provenience_id' => $provenience->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente com proveniência',
            'provenience_id' => $provenience->id,
        ]);
    }
}
