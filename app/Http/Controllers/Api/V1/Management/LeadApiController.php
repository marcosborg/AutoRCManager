<?php

namespace App\Http\Controllers\Api\V1\Management;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\RolePreview;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadApiController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('lead_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(Lead::STATUS_SELECT))],
        ]);

        $baseQuery = $this->visibleLeadsQuery();
        $summary = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $leads = $baseQuery
            ->with('assigned_user:id,name')
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['search'] ?? null, function ($query, $search) {
                $search = trim($search);
                $query->where(function ($query) use ($search) {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('vehicle_interest', 'like', "%{$search}%")
                        ->orWhereHas('assigned_user', fn ($seller) => $seller->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Lead $lead) => $this->listPayload($lead));

        return response()->json([
            'data' => $leads,
            'statuses' => Lead::STATUS_SELECT,
            'summary' => collect(Lead::STATUS_SELECT)->mapWithKeys(
                fn ($label, $status) => [$status => (int) ($summary[$status] ?? 0)]
            ),
        ]);
    }

    public function show(Lead $lead)
    {
        abort_if(Gate::denies('lead_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canAccess($lead), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $lead->load(['assigned_user:id,name', 'notes.user:id,name', 'assignment_histories.user:id,name', 'assignment_histories.assigned_by:id,name']);

        return response()->json([
            'data' => $this->detailPayload($lead),
            'statuses' => Lead::STATUS_SELECT,
        ]);
    }

    private function visibleLeadsQuery()
    {
        return Lead::query()->when(
            ! $this->isLeadManager(),
            fn ($query) => $query->where('assigned_user_id', auth()->id())
        );
    }

    private function canAccess(Lead $lead): bool
    {
        return $this->isLeadManager() || (int) $lead->assigned_user_id === (int) auth()->id();
    }

    private function isLeadManager(): bool
    {
        return RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm', 'Marketing Stand']);
    }

    private function listPayload(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'full_name' => $lead->full_name ?: trim(($lead->first_name ?? '').' '.($lead->last_name ?? '')),
            'phone' => $lead->phone,
            'email' => $lead->email,
            'vehicle_interest' => $lead->vehicle_interest,
            'budget' => $lead->budget,
            'status' => $lead->status,
            'status_label' => Lead::STATUS_SELECT[$lead->status] ?? $lead->status,
            'source' => $this->isWhatsapp($lead) ? 'WhatsApp' : 'Formulário',
            'assigned_user' => $lead->assigned_user?->name,
            'created_at' => optional($lead->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function detailPayload(Lead $lead): array
    {
        return $this->listPayload($lead) + [
            'assigned_user_id' => $lead->assigned_user_id,
            'financing' => $lead->financing,
            'trade_in' => $lead->trade_in,
            'purchase_timeline' => data_get($lead->raw_data, 'purchase_timeline') ?: data_get($lead->raw_data, 'qualification.purchase_timeline'),
            'wants_visit' => data_get($lead->raw_data, 'wants_visit') ?: data_get($lead->raw_data, 'qualification.wants_visit'),
            'notes' => $lead->notes->sortByDesc('created_at')->values()->map(fn ($note) => [
                'id' => $note->id,
                'body' => $note->body,
                'user' => $note->user?->name,
                'created_at' => optional($note->created_at)->format('Y-m-d H:i'),
            ]),
            'assignment_history' => $lead->assignment_histories->sortByDesc('created_at')->values()->map(fn ($history) => [
                'id' => $history->id,
                'user' => $history->user?->name,
                'assigned_by' => $history->assigned_by?->name,
                'reason' => $history->reason,
                'created_at' => optional($history->created_at)->format('Y-m-d H:i'),
            ]),
        ];
    }

    private function isWhatsapp(Lead $lead): bool
    {
        return data_get($lead->raw_data, 'source') === 'ai_whatsapp'
            || $lead->form_id === 'ai_whatsapp'
            || str_starts_with((string) $lead->leadgen_id, 'ai_whatsapp:');
    }
}
