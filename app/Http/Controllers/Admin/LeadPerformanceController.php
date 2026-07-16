<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\User;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadPerformanceController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('lead_performance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $measurementStartedAt = LeadAssignmentHistory::query()
            ->whereHas('access_tokens', fn ($query) => $query->whereNotNull('assignment_history_id'))
            ->min('created_at');
        $measurementStart = $measurementStartedAt ? Carbon::parse($measurementStartedAt)->startOfDay() : now()->startOfDay();

        $requestedStart = $this->date($request->query('date_start')) ?: now()->startOfYear();
        $dateStart = $requestedStart->lt($measurementStart) ? $measurementStart->copy() : $requestedStart;
        $dateEnd = $this->date($request->query('date_end')) ?: now()->endOfYear();
        if ($dateEnd->lt($dateStart)) {
            $dateEnd = $dateStart->copy();
        }

        $sellerId = $request->integer('seller_id') ?: null;
        $source = in_array($request->query('source'), ['form', 'whatsapp'], true) ? $request->query('source') : null;
        $channel = in_array($request->query('channel'), ['call', 'whatsapp'], true) ? $request->query('channel') : null;

        $opportunities = LeadAssignmentHistory::query()
            ->with(['user', 'lead', 'access_tokens.contact_events', 'contact_events'])
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$dateStart->copy()->startOfDay(), $dateEnd->copy()->endOfDay()])
            ->whereHas('access_tokens', fn ($query) => $query->whereNotNull('assignment_history_id'))
            ->when($sellerId, fn ($query) => $query->where('user_id', $sellerId))
            ->when($source, fn ($query) => $query->whereHas('lead', function ($leadQuery) use ($source) {
                $source === 'whatsapp' ? $this->whereWhatsapp($leadQuery) : $this->whereForm($leadQuery);
            }))
            ->get()
            ->map(fn (LeadAssignmentHistory $assignment) => $this->opportunity($assignment, $channel));

        $ranking = $opportunities
            ->groupBy('user_id')
            ->map(function ($items) {
                $assigned = $items->count();
                $opened = $items->where('opened', true)->count();
                $contacted = $items->where('contacted', true)->count();

                return [
                    'user_id' => $items->first()['user_id'],
                    'user_name' => $items->first()['user_name'],
                    'assigned' => $assigned,
                    'opened' => $opened,
                    'open_rate' => $assigned ? $opened / $assigned * 100 : 0,
                    'unopened' => $assigned - $opened,
                    'contacted' => $contacted,
                    'contact_rate' => $assigned ? $contacted / $assigned * 100 : 0,
                    'calls' => $items->where('has_call', true)->count(),
                    'whatsapps' => $items->where('has_whatsapp', true)->count(),
                    'avg_open_minutes' => $this->average($items->pluck('open_minutes')->filter(fn ($value) => $value !== null)),
                    'avg_contact_minutes' => $this->average($items->pluck('contact_minutes')->filter(fn ($value) => $value !== null)),
                ];
            })
            ->sortByDesc(fn ($row) => sprintf('%010.4f-%010d', $row['contact_rate'], $row['contacted']))
            ->values();

        $metric = in_array($request->query('metric'), ['assigned', 'opened', 'contacted', 'unopened', 'call', 'whatsapp'], true)
            ? $request->query('metric') : null;
        $detail = ($sellerId && $metric)
            ? $opportunities->filter(fn ($row) => $this->matchesMetric($row, $metric))->values()
            : collect();

        $salespeople = User::query()->whereHas('roles', fn ($query) => $query->where('title', 'Stand'))
            ->orderBy('name')->pluck('name', 'id');

        return view('admin.leads.performance', compact(
            'ranking', 'detail', 'salespeople', 'measurementStart', 'dateStart', 'dateEnd',
            'sellerId', 'source', 'channel', 'metric'
        ));
    }

    private function opportunity(LeadAssignmentHistory $assignment, ?string $channel): array
    {
        $tokens = $assignment->access_tokens;
        $openedAt = $tokens->pluck('last_used_at')->filter()->sort()->first();
        $events = $assignment->contact_events
            ->when($channel, fn ($items) => $items->where('channel', $channel))
            ->sortBy('clicked_at');
        $firstContact = $events->first()?->clicked_at;
        $assignedAt = $assignment->created_at;
        $lead = $assignment->lead;

        return [
            'assignment_id' => $assignment->id,
            'lead_id' => $assignment->lead_id,
            'lead_name' => $lead?->full_name ?: trim(($lead?->first_name ?? '').' '.($lead?->last_name ?? '')) ?: 'Lead #'.$assignment->lead_id,
            'status' => Lead::STATUS_SELECT[$lead?->status] ?? $lead?->status,
            'source' => $this->isWhatsapp($lead) ? 'WhatsApp' : 'Formulário',
            'user_id' => $assignment->user_id,
            'user_name' => $assignment->user?->name ?? 'Utilizador removido',
            'assigned_at' => $assignedAt,
            'opened_at' => $openedAt,
            'first_contact_at' => $firstContact,
            'opened' => $openedAt !== null,
            'contacted' => $firstContact !== null,
            'has_call' => $events->contains('channel', 'call'),
            'has_whatsapp' => $events->contains('channel', 'whatsapp'),
            'channels' => $events->pluck('channel')->unique()->map(fn ($item) => $item === 'call' ? 'Telefone' : 'WhatsApp')->implode(', '),
            'clicks' => $events->count(),
            'expired' => ! $openedAt && $tokens->contains('revoked_reason', 'no_open_timeout'),
            'open_minutes' => $openedAt ? $assignedAt->diffInMinutes($openedAt) : null,
            'contact_minutes' => $firstContact ? $assignedAt->diffInMinutes($firstContact) : null,
        ];
    }

    private function matchesMetric(array $row, string $metric): bool
    {
        return match ($metric) {
            'opened' => $row['opened'], 'contacted' => $row['contacted'], 'unopened' => ! $row['opened'],
            'call' => $row['has_call'], 'whatsapp' => $row['has_whatsapp'], default => true,
        };
    }

    private function average($values): ?int { return $values->count() ? (int) round($values->avg()) : null; }
    private function date($value): ?Carbon { try { return $value ? Carbon::createFromFormat('Y-m-d', $value) : null; } catch (\Throwable) { return null; } }
    private function isWhatsapp(?Lead $lead): bool { return $lead && (data_get($lead->raw_data, 'source') === 'ai_whatsapp' || $lead->form_id === 'ai_whatsapp' || str_starts_with((string) $lead->leadgen_id, 'ai_whatsapp:')); }
    private function whereWhatsapp($query): void { $query->where(fn ($q) => $q->where('raw_data->source', 'ai_whatsapp')->orWhere('form_id', 'ai_whatsapp')->orWhere('leadgen_id', 'like', 'ai_whatsapp:%')); }
    private function whereForm($query): void { $query->where(fn ($q) => $q->whereNull('raw_data->source')->orWhere('raw_data->source', '!=', 'ai_whatsapp'))->where(fn ($q) => $q->whereNull('form_id')->orWhere('form_id', '!=', 'ai_whatsapp'))->where('leadgen_id', 'not like', 'ai_whatsapp:%'); }
}
