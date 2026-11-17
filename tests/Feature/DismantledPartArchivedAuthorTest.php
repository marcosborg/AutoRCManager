<?php

namespace Tests\Feature;

use App\Models\{DismantledPart, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class DismantledPartArchivedAuthorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_archived_author_remains_identifiable_without_granting_edit_permission(): void
    {
        $author = User::factory()->create(['name' => 'Autor arquivado do inventário']);
        $this->actingAs($author);
        $vehicle = Vehicle::create(['license' => 'AUTHOR-TEST']);
        $part = DismantledPart::create([
            'vehicle_id' => $vehicle->id,
            'description' => 'Peça com origem preservada',
            'condition' => 'untested',
            'location' => 'Prateleira A',
            'created_by_id' => $author->id,
            'submission_token' => (string) Str::uuid(),
        ]);
        $before = $part->fresh()->getAttributes();
        $revision = $part->fresh()->revisionToken();
        $author->delete();

        $reader = User::factory()->create();
        $role = Role::create(['title' => uniqid('Inventário consulta ')]);
        $role->permissions()->attach(Permission::firstOrCreate(['title' => 'repair_access']));
        $reader->roles()->attach($role);
        $this->actingAs($reader);

        $this->assertSame($author->id, $part->fresh()->createdBy->id);
        $this->assertTrue($part->fresh()->createdBy->trashed());
        $this->get(route('admin.dismantled-parts.index', ['vehicle_id' => $vehicle->id]))
            ->assertOk()->assertSee($author->name)->assertSee($part->description)
            ->assertDontSee('Corrigir ficha');
        $this->get(route('admin.dismantled-parts.history', $part))
            ->assertOk()->assertSee($author->name)->assertSee('Registo inicial');
        $this->get(route('admin.dismantled-parts.edit', $part))->assertForbidden();
        $this->assertSame($before, $part->fresh()->getAttributes());
        $this->assertSame($revision, $part->fresh()->revisionToken());

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.dismantled-parts.index'))->assertForbidden();
        $this->get(route('admin.dismantled-parts.history', $part))->assertForbidden();
    }
}
