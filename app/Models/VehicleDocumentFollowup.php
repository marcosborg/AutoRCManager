<?php

namespace App\Models;

use App\Support\VehicleDocuments;
use Illuminate\Database\Eloquent\Model;

class VehicleDocumentFollowup extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['history' => 'array', 'version' => 'integer', 'purchase_form_data' => 'array'];

    public const LABELS = ['open' => 'Em acompanhamento', 'completed' => 'Concluído com cópia digital', 'dispensed' => 'Dispensado', 'review' => 'Rever acompanhamento'];

    public function statusLabel(): string
    {
        return in_array($this->document, ['signed_purchase_form', 'signed_sale_declaration', 'signed_warranty_declaration'], true) && $this->status === 'open'
            ? 'À espera de assinatura' : self::LABELS[$this->status];
    }

    public function purchaseSignatureAgeAlertDays(?\Carbon\CarbonInterface $reference = null): ?int
    {
        if ($this->document !== 'signed_purchase_form' || $this->status !== 'open') return null;
        $requested = (string) $this->requested_on;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested)) return null;
        try {
            $start = \Carbon\CarbonImmutable::createFromFormat('!Y-m-d', $requested, 'Europe/Lisbon');
        } catch (\Throwable) {
            return null;
        }
        if (!$start || $start->format('Y-m-d') !== $requested) return null;
        $today = ($reference ? \Carbon\CarbonImmutable::instance($reference) : \Carbon\CarbonImmutable::now('Europe/Lisbon'))
            ->setTimezone('Europe/Lisbon')->startOfDay();
        $days = (int) $start->diffInDays($today, false);

        return $days >= 3 ? $days : null;
    }

    public static function responsibleOptions()
    {
        return User::with('roles.permissions')->orderBy('name')->get()->filter(fn ($user) =>
            \Illuminate\Support\Facades\Gate::forUser($user)->allows('vehicle_access')
            && \Illuminate\Support\Facades\Gate::forUser($user)->allows('vehicle_edit')
            && \Illuminate\Support\Facades\Gate::forUser($user)->allows('vehicle_documents_area_access'));
    }

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
        if ($action === 'prepare_purchase') {
            $history[array_key_last($history)]['purchase_form_data'] = $this->purchase_form_data;
        }
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
