<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Permission, Role, User, Vehicle, VehicleNote};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class VehicleNoteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_note_preserves_vehicle_fields_and_records_authenticated_author_and_time(): void
    {
        $vehicle = $this->vehicle();
        $vehicle->update(['acquisition_notes' => 'Nota antiga da aquisição']);
        $before = $vehicle->fresh()->getRawOriginal();
        $user = $this->user(['vehicle_edit']);
        $this->travelTo(now()->startOfSecond());
        $payload = $this->payload("Verificar segunda chave.\nConfirmar com a equipa.") + ['author_id' => 0, 'author_name' => 'Autor falso', 'vehicle_id' => 0];
        $this->actingAs($user)->post(route('admin.vehicles.notes.store', $vehicle), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.vehicles.notes.index', $vehicle));
        $note = VehicleNote::where('vehicle_id', $vehicle->id)->sole();
        $this->assertEquals($user->id, $note->author_id);
        $this->assertSame($user->name, $note->author_name);
        $this->assertSame($payload['body'], $note->body);
        $this->assertTrue($note->created_at->equalTo(now()));
        $this->assertSame($before, $vehicle->fresh()->getRawOriginal());
        $this->assertDatabaseHas('audit_logs', ['subject_type' => VehicleNote::class.'#'.$note->id, 'user_id' => $user->id, 'description' => 'audit:created']);
        $this->get(route('admin.vehicles.notes.index', $vehicle))->assertOk()->assertSee('Verificar segunda chave.')->assertSee($user->name)->assertSee(now()->timezone('Europe/Lisbon')->format('d/m/Y H:i'));
    }

    public function test_repeated_submission_is_idempotent_but_separate_notes_are_kept(): void
    {
        $vehicle = $this->vehicle(); $this->actingAs($this->user(['vehicle_edit']));
        $payload = $this->payload('Confirmar a entrega.');
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('admin.vehicles.notes.store', $vehicle), $payload)->assertSessionHasNoErrors();
        }
        $this->assertSame(1, VehicleNote::where('vehicle_id', $vehicle->id)->count());
        $changed = $payload; $changed['body'] = 'Texto diferente no mesmo envio';
        $this->post(route('admin.vehicles.notes.store', $vehicle), $changed)->assertSessionHasErrors('body');
        $this->post(route('admin.vehicles.notes.store', $vehicle), $this->payload('Outra informação.'))->assertSessionHasNoErrors();
        $this->assertSame(2, VehicleNote::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_empty_oversized_and_invalid_submissions_are_rejected(): void
    {
        $vehicle = $this->vehicle(); $this->actingAs($this->user(['vehicle_edit']));
        foreach (['', " \n\t", "\u{00A0}", str_repeat('a', 5001)] as $body) {
            $this->post(route('admin.vehicles.notes.store', $vehicle), $this->payload($body))->assertSessionHasErrors('body');
        }
        $this->post(route('admin.vehicles.notes.store', $vehicle), ['body' => 'Nota'])->assertSessionHasErrors('submission_token');
        $this->assertSame(0, VehicleNote::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_permissions_are_enforced_and_read_only_users_cannot_add_notes(): void
    {
        $vehicle = $this->vehicle();
        $this->get(route('admin.vehicles.notes.index', $vehicle))->assertRedirect(route('login'));
        $this->actingAs($this->user([]))->get(route('admin.vehicles.notes.index', $vehicle))->assertForbidden();
        $this->post(route('admin.vehicles.notes.store', $vehicle), $this->payload('Bloqueada'))->assertForbidden();
        $this->actingAs($this->user(['vehicle_show']))->get(route('admin.vehicles.notes.index', $vehicle))->assertOk()->assertDontSee('Adicionar nota');
        $this->post(route('admin.vehicles.notes.store', $vehicle), $this->payload('Bloqueada'))->assertForbidden();
        $this->assertSame(0, VehicleNote::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_notes_are_vehicle_specific_paginated_and_html_is_escaped(): void
    {
        $vehicle = $this->vehicle(); $other = $this->vehicle(); $user = $this->user(['vehicle_edit']);
        $this->actingAs($user);
        for ($i = 1; $i <= 21; $i++) {
            $this->post(route('admin.vehicles.notes.store', $vehicle), $this->payload(sprintf('Nota sequencial %02d', $i)))->assertSessionHasNoErrors();
        }
        $this->get(route('admin.vehicles.notes.index', $vehicle))->assertSee('Nota sequencial 21')->assertDontSee('Nota sequencial 01');
        $this->get(route('admin.vehicles.notes.index', $vehicle).'?page=2')->assertSee('Nota sequencial 01');
        $this->get(route('admin.vehicles.notes.index', $other))->assertDontSee('Nota sequencial')->assertSee('Ainda não existem notas');
        $body = '<script>alert("nota")</script>';
        $this->post(route('admin.vehicles.notes.store', $other), $this->payload($body))->assertSessionHasNoErrors();
        $this->get(route('admin.vehicles.notes.index', $other))->assertSee($body)->assertDontSee($body, false);
    }

    public function test_deleted_vehicles_are_inaccessible_and_author_identity_is_preserved(): void
    {
        $vehicle = $this->vehicle(); $author = $this->user(['vehicle_edit']);
        $this->actingAs($author)->post(route('admin.vehicles.notes.store', $vehicle), $this->payload('Histórico'))->assertSessionHasNoErrors();
        $name = $author->name; $author->update(['name' => 'Nome alterado']); $author->delete();
        $this->actingAs($this->user(['vehicle_show']))->get(route('admin.vehicles.notes.index', $vehicle))->assertSee($name);
        $vehicle->delete();
        $this->get(route('admin.vehicles.notes.index', $vehicle))->assertNotFound();
        $this->assertSame(1, VehicleNote::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_vehicle_pages_link_to_the_internal_conversation(): void
    {
        $vehicle = $this->vehicle(); $this->actingAs($this->user(['vehicle_show', 'vehicle_edit']));
        foreach (['show', 'edit'] as $action) {
            $this->get(route('admin.vehicles.'.$action, $vehicle))->assertOk()->assertSee(route('admin.vehicles.notes.index', $vehicle))->assertSee('Notas internas');
        }
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create(['license' => uniqid('NOTE-')]);
    }

    private function payload(string $body): array
    {
        return ['body' => $body, 'submission_token' => (string) Str::uuid()];
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create(); $role = Role::create(['title' => uniqid('Vehicle notes ')]);
        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        }
        $user->roles()->attach($role); return $user;
    }
}
