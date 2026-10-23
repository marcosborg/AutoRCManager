<?php

namespace App\Models;

use App\Support\VehicleDocuments;
use Illuminate\Database\Eloquent\Model;

class VehicleDocumentFollowup extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['history' => 'array', 'version' => 'integer'];

    public const LABELS = ['open' => 'Em acompanhamento', 'completed' => 'Concluído com cópia digital', 'dispensed' => 'Dispensado', 'review' => 'Rever acompanhamento'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function record(string $action, string $reason, ?int $attachmentId = null): void
    {
        $history = $this->history ?? [];
        $history[] = ['action' => $action, 'reason' => $reason, 'at' => now()->toIso8601String(),
            'user_id' => auth()->id(), 'user_name' => auth()->user()?->name ?? 'Sistema',
            'assigned_to_id' => $this->assigned_to_id, 'responsible_name' => User::find($this->assigned_to_id)?->name, 'contact' => $this->contact,
            'requested_on' => $this->requested_on, 'next_contact_on' => $this->next_contact_on,
            'attachment_id' => $attachmentId, 'media_id' => $this->media_id, 'status' => $this->status];
        $this->history = $history;
        $this->version++;
        $this->save();
        $this->syncReminder();
    }

    public function syncReminder(): void
    {
        $key = 'document-followup:'.$this->id;
        $task = CalendarTask::firstOrNew(['dedupe_key' => $key]);
        $task->fill(['title' => mb_substr('Documento: '.(VehicleDocuments::applicabilityDocuments()[$this->document] ?? $this->document).' — '.($this->vehicle?->license ?? '#'.$this->vehicle_id), 0, 191),
            'vehicle_id' => $this->vehicle_id, 'assigned_to_id' => $this->assigned_to_id,
            'type' => 'vehicle_document_followup', 'recipient_group' => null,
            'created_by_id' => $task->created_by_id ?? auth()->id(),
            'target_url' => route('admin.vehicles.documents.followup', [$this->vehicle_id, $this->document], false),
            'notes' => 'Consultar o acompanhamento documental.',
            'completed_at' => $this->status === 'open' ? null : ($task->completed_at ?? now())]);
        $task->due_date = \Carbon\Carbon::parse($this->next_contact_on)->format(config('panel.date_format'));
        $task->save();
    }
}
