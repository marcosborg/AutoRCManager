<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleOwnershipTransferTest extends TestCase
{
    use DatabaseTransactions;

    public function test_edit_page_contains_the_ownership_transfer_tab(): void
    {
        $user = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $vehicle = Vehicle::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.vehicles.edit', $vehicle))
            ->assertOk()
            ->assertSee('Transferência de Propriedade')
            ->assertSee('Documentação pronta')
            ->assertSee('Pagamentos efetuados')
            ->assertSee('Autorização do Rafael')
            ->assertSee('ownership_transfer_proof-dropzone')
            ->assertSee('ownership_rafael_authorization_proof-dropzone');
    }

    public function test_ownership_transfer_timestamps_can_be_saved_edited_and_cleared(): void
    {
        Storage::fake('public');

        $user = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $vehicle = Vehicle::query()->firstOrFail();
        $proofFileName = 'ownership-transfer-proof.pdf';
        $rafaelAuthorizationProofFileName = 'rafael-authorization-proof.pdf';

        UploadedFile::fake()
            ->create($proofFileName, 100, 'application/pdf')
            ->move(storage_path('tmp/uploads'), $proofFileName);
        UploadedFile::fake()
            ->create($rafaelAuthorizationProofFileName, 100, 'application/pdf')
            ->move(storage_path('tmp/uploads'), $rafaelAuthorizationProofFileName);

        $this->actingAs($user)
            ->put(route('admin.vehicles.update', $vehicle), [
                'general_state_id' => $vehicle->general_state_id,
                'ownership_documents_ready' => 1,
                'ownership_documents_ready_at' => '2026-07-14T09:30',
                'ownership_payments_completed' => 1,
                'ownership_payments_completed_at' => '2026-07-14T10:45',
                'ownership_rafael_authorized' => 1,
                'ownership_rafael_authorized_at' => '2026-07-14T11:15',
                'ownership_transfer_proof' => [$proofFileName],
                'ownership_rafael_authorization_proof' => [$rafaelAuthorizationProofFileName],
            ])
            ->assertRedirect();

        $vehicle->refresh();

        $this->assertSame('2026-07-14 09:30', $vehicle->ownership_documents_ready_at?->format('Y-m-d H:i'));
        $this->assertSame('2026-07-14 10:45', $vehicle->ownership_payments_completed_at?->format('Y-m-d H:i'));
        $this->assertSame('2026-07-14 11:15', $vehicle->ownership_rafael_authorized_at?->format('Y-m-d H:i'));
        $this->assertCount(1, $vehicle->ownership_transfer_proof);
        $this->assertSame($proofFileName, $vehicle->ownership_transfer_proof->first()->file_name);
        $this->assertCount(1, $vehicle->ownership_rafael_authorization_proof);
        $this->assertSame($rafaelAuthorizationProofFileName, $vehicle->ownership_rafael_authorization_proof->first()->file_name);

        $this->actingAs($user)
            ->put(route('admin.vehicles.update', $vehicle), [
                'general_state_id' => $vehicle->general_state_id,
                'ownership_documents_ready' => 0,
                'ownership_payments_completed' => 0,
                'ownership_rafael_authorized' => 0,
            ])
            ->assertRedirect();

        $vehicle->refresh();

        $this->assertNull($vehicle->ownership_documents_ready_at);
        $this->assertNull($vehicle->ownership_payments_completed_at);
        $this->assertNull($vehicle->ownership_rafael_authorized_at);
    }
}
