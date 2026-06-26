<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use App\Services\PendingLeadSmtpNotificationService;
use App\Support\RolePreview;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('lead_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = $this->visibleLeadsQuery()
                ->with('assigned_user')
                ->select(sprintf('%s.*', (new Lead)->table));

            $table = DataTables::of($query);
            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'lead_show';
                $editGate = 'lead_edit';
                $deleteGate = 'lead_delete';
                $crudRoutePart = 'leads';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('created_at', fn (Lead $row) => optional($row->created_at)->format('Y-m-d H:i'));
            $table->addColumn('source_label', fn (Lead $row) => $this->sourceLabel($row));
            $table->editColumn('full_name', fn (Lead $row) => $row->full_name ?: trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')));
            $table->editColumn('phone', fn (Lead $row) => $row->phone ?: '');
            $table->editColumn('email', fn (Lead $row) => $row->email ?: '');
            $table->editColumn('budget', fn (Lead $row) => $row->budget ?: '');
            $table->editColumn('vehicle_interest', fn (Lead $row) => $row->vehicle_interest ?: '');
            $table->addColumn('assigned_user_name', fn (Lead $row) => $row->assigned_user?->name ?: '');
            $table->editColumn('status', fn (Lead $row) => Lead::STATUS_SELECT[$row->status] ?? $row->status);
            $table->filterColumn('full_name', function ($query, $keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where('full_name', 'like', '%' . $keyword . '%')
                        ->orWhere('first_name', 'like', '%' . $keyword . '%')
                        ->orWhere('last_name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%');
                });
            });
            $table->filterColumn('status', function ($query, $keyword) {
                $value = trim((string) $keyword, " \t\n\r\0\x0B^$");
                if (array_key_exists($value, Lead::STATUS_SELECT)) {
                    $query->where('status', $value);
                }
            });
            $table->filterColumn('source_label', function ($query, $keyword) {
                $value = trim((string) $keyword, " \t\n\r\0\x0B^$");

                if ($value === 'whatsapp') {
                    $query->where(function ($subQuery) {
                        $this->whereWhatsappSource($subQuery);
                    });
                } elseif ($value === 'form') {
                    $query->where(function ($subQuery) {
                        $this->whereFormSource($subQuery);
                    });
                }
            });

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        $statuses = Lead::STATUS_SELECT;

        return view('admin.leads.index', compact('statuses'));
    }

    public function sendPendingSmtp(Request $request, PendingLeadSmtpNotificationService $service)
    {
        abort_if(Gate::denies('lead_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->isLeadManager(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $limit = max(1, min(100, (int) $request->integer('limit', 50)));
        $result = $service->send($limit);

        return redirect()
            ->route('admin.leads.index')
            ->with('message', sprintf(
                'SMTP leads processadas: %d; enviadas: %d; falhadas: %d.',
                $result['processed'],
                $result['sent'],
                $result['failed']
            ));
    }

    public function show(Lead $lead)
    {
        abort_if(Gate::denies('lead_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canAccessLead($lead), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $lead->load(['assigned_user', 'assignment_histories.user', 'assignment_histories.assigned_by', 'notes.user']);
        $statuses = Lead::STATUS_SELECT;
        $salespeople = $this->salespeople();

        return view('admin.leads.show', compact('lead', 'statuses', 'salespeople'));
    }

    public function edit(Lead $lead)
    {
        abort_if(Gate::denies('lead_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canAccessLead($lead), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $statuses = Lead::STATUS_SELECT;
        $salespeople = $this->salespeople();

        return view('admin.leads.edit', compact('lead', 'statuses', 'salespeople'));
    }

    public function update(Request $request, Lead $lead)
    {
        abort_if(Gate::denies('lead_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canAccessLead($lead), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Lead::STATUS_SELECT))],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'vehicle_interest' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'string', 'max:255'],
            'financing' => ['nullable', 'string', 'max:255'],
            'trade_in' => ['nullable', 'string', 'max:255'],
        ]);

        $oldAssignedUserId = $lead->assigned_user_id;
        $lead->update($data);

        if ((int) $oldAssignedUserId !== (int) ($data['assigned_user_id'] ?? 0)) {
            $lead->assignment_histories()->create([
                'user_id' => $data['assigned_user_id'] ?? null,
                'assigned_by_id' => $request->user()?->id,
                'reason' => 'manual',
            ]);
        }

        return redirect()->route('admin.leads.show', $lead)->with('message', 'Lead atualizado.');
    }

    public function destroy(Lead $lead)
    {
        abort_if(Gate::denies('lead_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->isLeadManager(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $lead->delete();

        return back();
    }

    public function storeNote(Request $request, Lead $lead)
    {
        abort_if(Gate::denies('lead_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canAccessLead($lead), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $lead->notes()->create([
            'user_id' => $request->user()?->id,
            'body' => $data['body'],
        ]);

        return redirect()->route('admin.leads.show', $lead)->with('message', 'Nota adicionada.');
    }

    public function destroyNote(Lead $lead, LeadNote $note)
    {
        abort_if(Gate::denies('lead_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canAccessLead($lead), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $note->lead_id !== (int) $lead->id, Response::HTTP_NOT_FOUND);

        $note->delete();

        return redirect()->route('admin.leads.show', $lead)->with('message', 'Nota removida.');
    }

    private function visibleLeadsQuery()
    {
        $query = Lead::query();

        if (! $this->isLeadManager()) {
            $query->where('assigned_user_id', auth()->id());
        }

        return $query;
    }

    private function canAccessLead(Lead $lead): bool
    {
        return $this->isLeadManager() || (int) $lead->assigned_user_id === (int) auth()->id();
    }

    private function isLeadManager(): bool
    {
        return RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm', 'Marketing Stand']);
    }

    private function salespeople()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('title', 'Stand'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('global.pleaseSelect'), '');
    }

    private function sourceLabel(Lead $lead): string
    {
        return $this->isWhatsappLead($lead) ? 'WhatsApp' : 'Formulário';
    }

    private function isWhatsappLead(Lead $lead): bool
    {
        return data_get($lead->raw_data, 'source') === 'ai_whatsapp'
            || $lead->form_id === 'ai_whatsapp'
            || str_starts_with((string) $lead->leadgen_id, 'ai_whatsapp:');
    }

    private function whereWhatsappSource($query): void
    {
        $query
            ->where('raw_data->source', 'ai_whatsapp')
            ->orWhere('form_id', 'ai_whatsapp')
            ->orWhere('leadgen_id', 'like', 'ai_whatsapp:%');
    }

    private function whereFormSource($query): void
    {
        $query
            ->where(function ($sourceQuery) {
                $sourceQuery->whereNull('raw_data->source')
                    ->orWhere('raw_data->source', '!=', 'ai_whatsapp');
            })
            ->where(function ($sourceQuery) {
                $sourceQuery->whereNull('form_id')
                    ->orWhere('form_id', '!=', 'ai_whatsapp');
            })
            ->where('leadgen_id', 'not like', 'ai_whatsapp:%');
    }
}
