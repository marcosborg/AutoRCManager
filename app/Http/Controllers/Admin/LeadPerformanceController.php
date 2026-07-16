<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadPerformanceController extends Controller
{
    public function export(Request $request)
    {
        $request->merge(['pdf' => 1]);

        return $this->index($request);
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('lead_performance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $measurementStartedAt = LeadAssignmentHistory::query()
            ->whereHas('access_tokens', fn ($query) => $query->whereNotNull('assignment_history_id'))
            ->min('created_at');
        $measurementStart = $measurementStartedAt ? Carbon::parse($measurementStartedAt)->startOfDay() : now()->startOfDay();

        $requestedStart = $this->date($request->query('date_start')) ?: now()->startOfYear();
        $filterDateStart = $requestedStart->copy();
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

        $legacyEnd = $dateEnd->copy()->endOfDay()->min($measurementStart->copy()->subSecond());
        $legacyRanking = (!$channel && $filterDateStart->lte($legacyEnd))
            ? $this->legacyRanking($filterDateStart, $legacyEnd, $sellerId, $source)
            : collect();

        $metric = in_array($request->query('metric'), ['assigned', 'opened', 'contacted', 'unopened', 'call', 'whatsapp'], true)
            ? $request->query('metric') : null;
        $detail = ($sellerId && $metric)
            ? $opportunities->filter(fn ($row) => $this->matchesMetric($row, $metric))->values()
            : collect();

        $salespeople = User::query()->whereHas('roles', fn ($query) => $query->where('title', 'Stand'))
            ->orderBy('name')->pluck('name', 'id');

        $data = compact(
            'ranking', 'legacyRanking', 'detail', 'salespeople', 'measurementStart', 'filterDateStart', 'dateStart', 'dateEnd',
            'sellerId', 'source', 'channel', 'metric'
        );

        if ($request->boolean('pdf')) {
            return $this->pdf($data);
        }

        return view('admin.leads.performance', $data);
    }

    private function pdf(array $data)
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.leads.performancePdf', $data + [
            'generatedAt' => now(),
        ])->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_text(750, 570, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 8, [0.35, 0.39, 0.45]);

        $filename = 'desempenho-leads-'.now()->format('Y-m-d-His').'.pdf';

        return response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    private function legacyRanking(Carbon $dateStart, Carbon $dateEnd, ?int $sellerId, ?string $source)
    {
        $tokens = \App\Models\LeadAccessToken::query()
            ->with(['user', 'lead'])
            ->whereNull('assignment_history_id')
            ->whereBetween('created_at', [$dateStart->copy()->startOfDay(), $dateEnd])
            ->whereNotNull('user_id')
            ->when($sellerId, fn ($query) => $query->where('user_id', $sellerId))
            ->when($source, fn ($query) => $query->whereHas('lead', function ($leadQuery) use ($source) {
                $source === 'whatsapp' ? $this->whereWhatsapp($leadQuery) : $this->whereForm($leadQuery);
            }))
            ->get();

        if ($tokens->isEmpty()) {
            return collect();
        }

        $histories = LeadAssignmentHistory::query()
            ->whereIn('lead_id', $tokens->pluck('lead_id')->unique())
            ->whereIn('user_id', $tokens->pluck('user_id')->filter()->unique())
            ->where('created_at', '<=', $dateEnd)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($history) => $history->lead_id.':'.$history->user_id);

        $opportunities = $tokens->groupBy(function ($token) use ($histories) {
            $matching = $histories->get($token->lead_id.':'.$token->user_id, collect())
                ->filter(fn ($history) => $history->created_at->lte($token->created_at))
                ->last();

            return $matching ? 'history:'.$matching->id : 'token:'.$token->id;
        })->map(function ($opportunityTokens) {
            $first = $opportunityTokens->first();
            $opened = $opportunityTokens->contains(fn ($token) => $token->last_used_at !== null);

            return [
                'user_id' => $first->user_id,
                'user_name' => $first->user?->name ?? 'Utilizador removido',
                'opened' => $opened,
                'expired' => ! $opened && $opportunityTokens->contains('revoked_reason', 'no_open_timeout'),
            ];
        });

        return $opportunities->groupBy('user_id')->map(function ($items) {
            $assigned = $items->count();
            $opened = $items->where('opened', true)->count();

            return [
                'user_name' => $items->first()['user_name'],
                'assigned' => $assigned,
                'opened' => $opened,
                'open_rate' => $assigned ? $opened / $assigned * 100 : 0,
                'expired' => $items->where('expired', true)->count(),
            ];
        })->sortByDesc('open_rate')->values();
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
