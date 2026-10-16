<?php

namespace Tests\Feature;

use App\Models\{GeneralState, Permission, Role, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleDocumentSummaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.document-summary-tests' => ['driver' => 'local']]);
        Storage::fake('document-summary-tests');
    }

    public function test_received_paper_does_not_imply_a_digital_file(): void
    {
        $vehicle = $this->vehicle(['vehicle_ownership_title' => 1]);
        $row = $this->row($this->page($vehicle), 'vehicle_ownership_title');
        $this->assertStringContainsString('Recebido segundo a ficha', $row);
        $this->assertStringContainsString('Sem ficheiro identificado', $row);
        $this->assertStringContainsString('Por confirmar', $this->row($this->page($vehicle), 'copy_of_the_citizen_card'));
    }

    public function test_legacy_attachments_are_not_classified_by_name_or_used_as_receipt_evidence(): void
    {
        $vehicle = $this->vehicle();
        $this->attach($vehicle, 'documents', 'DUA-inspecao.pdf');
        $this->attach($vehicle, 'additional_documents', 'cartao-cidadao.pdf');
        $before = $vehicle->fresh()->media->toJson();
        $page = $this->page($vehicle);
        $page->assertSee('Ficheiros disponíveis por identificar')->assertSee('DUA / Inspeção: 1')->assertSee('Outros documentos: 1');
        foreach (['vehicle_ownership_title', 'inspection', 'copy_of_the_citizen_card'] as $field) {
            $this->assertStringContainsString('Por confirmar', $this->row($page, $field));
            $this->assertStringContainsString('Sem ficheiro identificado', $this->row($page, $field));
        }
        $this->assertSame($before, $vehicle->fresh()->media->toJson());
        $this->assertFalse((bool) $vehicle->fresh()->vehicle_ownership_title);
    }

    public function test_empty_and_typed_document_groups_are_distinguished(): void
    {
        $vehicle = $this->vehicle();
        $page = $this->page($vehicle);
        $page->assertDontSee('Ficheiros disponíveis por identificar');
        $this->assertStringContainsString('Por confirmar', $this->row($page, 'dav'));
        $this->attach($vehicle, 'dav', 'documento.pdf');
        $page = $this->page($vehicle);
        $this->assertStringContainsString('Ficheiro identificado: 1 no grupo DAV', $this->row($page, 'dav'));
        $this->assertStringNotContainsString('Recebido segundo a ficha', $this->row($page, 'dav'));
    }

    public function test_summary_follows_saved_checks_after_reopening_and_preserves_attachments(): void
    {
        $vehicle = $this->vehicle();
        $media = $this->attach($vehicle, 'documents', 'antigo.pdf');
        $user = $this->user();
        foreach ([1, 0] as $received) {
            $this->actingAs($user)->put(route('admin.vehicles.update', $vehicle), [
                'general_state_id' => $vehicle->general_state_id,
                'license' => $vehicle->license,
                'vehicle_ownership_title' => $received,
                'documents' => [$media->file_name],
            ])->assertSessionHasNoErrors()->assertRedirect();
            $page = $this->actingAs($user)->get(route('admin.vehicles.edit', $vehicle))->assertOk();
            $this->assertStringContainsString($received ? 'Recebido segundo a ficha' : 'Por confirmar', $this->row($page, 'vehicle_ownership_title'));
            $this->assertSame([$media->id], $vehicle->fresh()->getMedia('documents')->pluck('id')->all());
            Storage::disk('document-summary-tests')->assertExists($media->getPathRelativeToRoot());
        }
    }

    public function test_summary_requires_the_existing_document_area_permission(): void
    {
        $vehicle = $this->vehicle();
        $this->actingAs(User::factory()->create())->get(route('admin.vehicles.edit', $vehicle))->assertForbidden();
        $this->actingAs($this->user(false))->get(route('admin.vehicles.edit', $vehicle))->assertOk()->assertDontSee('id="document-summary"', false);
        $this->page($vehicle)->assertSee('id="document-summary"', false);
    }

    private function row($response, string $field): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $node = (new \DOMXPath($dom))->query('//section[@id="document-summary"]//tr[@data-document="'.$field.'"]')->item(0);
        $this->assertNotNull($node);
        return $node->textContent;
    }

    private function page(Vehicle $vehicle)
    {
        return $this->actingAs($this->user())->get(route('admin.vehicles.edit', $vehicle))->assertOk();
    }

    private function vehicle(array $attributes = []): Vehicle
    {
        return Vehicle::create($attributes + ['license' => uniqid('DOC-'), 'general_state_id' => GeneralState::firstOrCreate(['name' => 'DOCUMENT TEST'])->id]);
    }

    private function attach(Vehicle $vehicle, string $collection, string $name)
    {
        return $vehicle->addMedia(UploadedFile::fake()->create($name, 1, 'application/pdf'))->toMediaCollection($collection, 'document-summary-tests');
    }

    private function user(bool $documents = true): User
    {
        $user = User::factory()->create();
        $role = Role::create(['title' => uniqid('Document summary ')]);
        foreach (array_merge(['vehicle_edit'], $documents ? ['vehicle_documents_area_access'] : []) as $title) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => $title])->id);
        }
        $user->roles()->attach($role);
        return $user;
    }
}
