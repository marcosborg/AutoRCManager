<?php

namespace Tests\Feature;

use App\Models\{CalendarTask, User, Vehicle};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DocumentFollowupReminderIdentificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reminder_uses_foreign_plate_or_vehicle_id_when_national_plate_is_empty(): void
    {
        $responsible = User::factory()->create();
        foreach ([[null, 'AB-CD-123', 'AB-CD-123'], ['', ' EF-456-GH ', 'EF-456-GH'], ['   ', '', null], [null, null, null], ['AA-01-AA', 'FOREIGN', 'AA-01-AA']] as [$national, $foreign, $expected]) {
            $vehicle = Vehicle::create(['license' => $national, 'foreign_license' => $foreign]);
            $followup = $vehicle->documentFollowups()->create(['document' => 'transport_guide', 'assigned_to_id' => $responsible->id, 'contact' => 'Transportador',
                'requested_on' => '2026-11-13', 'next_contact_on' => '2026-11-14', 'status' => 'open', 'version' => 0, 'history' => []]);
            $before = $vehicle->fresh()->getAttributes();
            $followup->record('contact', 'Pedir guia');
            $task = CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->sole();
            $this->assertStringEndsWith(' — '.($expected ?? '#'.$vehicle->id), $task->title);
            $this->assertSame(route('admin.vehicles.documents.followup', [$vehicle->id, 'transport_guide'], false), $task->target_url);
            $this->assertSame($vehicle->id, $task->vehicle_id);
            $this->assertNull($task->completed_at);
            $this->assertSame($before, $vehicle->fresh()->getAttributes());
            $followup->record('contact', 'Novo contacto');
            $this->assertSame(1, CalendarTask::where('dedupe_key', 'document-followup:'.$followup->id)->count());
            $this->assertSame('2026-11-14', $task->fresh()->getRawOriginal('due_date'));
        }
    }
}
