<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOficinaExpertiseProcessRequest;
use App\Http\Requests\UpdateOficinaExpertiseProcessRequest;
use App\Models\OficinaExpertiseProcess;
use App\Models\OficinaExpertiseProcessHistory;
use App\Models\User;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OficinaExpertiseProcessController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('oficina_expertise_process_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = OficinaExpertiseProcess::with(['vehicle.brand', 'vehicle.client', 'created_by', 'latest_status_history'])
            ->latest();

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('created_by_id'), fn ($q) => $q->where('created_by_id', $request->integer('created_by_id')));
        $query->when($request->filled('insurance_company'), fn ($q) => $q->where('insurance_company', 'like', '%' . trim((string) $request->insurance_company) . '%'));
        $query->when($request->filled('client_search'), function ($q) use ($request) {
            $search = trim((string) $request->client_search);
            $q->whereHas('vehicle.client', fn ($client) => $client->where('name', 'like', '%' . $search . '%'));
        });
        $query->when($request->filled('vehicle_search'), function ($q) use ($request) {
            $search = trim((string) $request->vehicle_search);
            $q->where(function ($sub) use ($search) {
                $sub->where('license', 'like', '%' . $search . '%')
                    ->orWhere('claim_number', 'like', '%' . $search . '%')
                    ->orWhere('process_number', 'like', '%' . $search . '%')
                    ->orWhereHas('vehicle', fn ($vehicle) => $vehicle->where(fn ($vehicleSub) => $vehicleSub->searchByLicense($search)->orWhere('model', 'like', '%' . $search . '%')));
            });
        });

        $processes = $query->paginate(40)->withQueryString();

        $kanbanProcesses = collect();
        if ($request->query('view') === 'kanban') {
            $kanbanProcesses = (clone $query)->limit(300)->get()->groupBy('status');
        }

        return view('admin.oficinaExpertiseProcesses.index', [
            'processes' => $processes,
            'kanbanProcesses' => $kanbanProcesses,
            'users' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('oficina_expertise_process_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.oficinaExpertiseProcesses.create', $this->formData(null, $request));
    }

    public function store(StoreOficinaExpertiseProcessRequest $request)
    {
        $process = DB::transaction(function () use ($request) {
            $data = $this->processData($request);
            $data['created_by_id'] = $request->user()?->id;
            $data['updated_by_id'] = $request->user()?->id;

            $process = OficinaExpertiseProcess::create($data);
            $this->recordHistory($process, null, $process->status, $request->user()?->id, $request->input('status_notes') ?: 'Processo criado.');
            $this->attachFiles($request, $process);

            return $process;
        });

        return redirect()->route('admin.oficina-expertise-processes.show', $process)->with('message', 'Peritagem criada com sucesso.');
    }

    public function show(OficinaExpertiseProcess $oficinaExpertiseProcess)
    {
        abort_if(Gate::denies('oficina_expertise_process_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $oficinaExpertiseProcess->load(['vehicle.brand', 'vehicle.client', 'created_by', 'updated_by', 'histories.changed_by', 'media', 'latest_status_history']);

        return view('admin.oficinaExpertiseProcesses.show', ['process' => $oficinaExpertiseProcess]);
    }

    public function edit(Request $request, OficinaExpertiseProcess $oficinaExpertiseProcess)
    {
        abort_if(Gate::denies('oficina_expertise_process_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.oficinaExpertiseProcesses.edit', $this->formData($oficinaExpertiseProcess, $request));
    }

    public function update(UpdateOficinaExpertiseProcessRequest $request, OficinaExpertiseProcess $oficinaExpertiseProcess)
    {
        DB::transaction(function () use ($request, $oficinaExpertiseProcess) {
            $oldStatus = $oficinaExpertiseProcess->status;
            $data = $this->processData($request);
            $data['updated_by_id'] = $request->user()?->id;

            if ($data['status'] === OficinaExpertiseProcess::STATUS_CLOSED && ! $oficinaExpertiseProcess->closed_at) {
                $data['closed_at'] = now();
            }

            if ($data['status'] !== OficinaExpertiseProcess::STATUS_CLOSED) {
                $data['closed_at'] = null;
            }

            $oficinaExpertiseProcess->update($data);
            $this->attachFiles($request, $oficinaExpertiseProcess);

            if ($oldStatus !== $oficinaExpertiseProcess->status) {
                $this->recordHistory(
                    $oficinaExpertiseProcess,
                    $oldStatus,
                    $oficinaExpertiseProcess->status,
                    $request->user()?->id,
                    $request->input('status_notes')
                );
            }
        });

        return redirect()->route('admin.oficina-expertise-processes.show', $oficinaExpertiseProcess)->with('message', 'Peritagem atualizada com sucesso.');
    }

    public function destroy(OficinaExpertiseProcess $oficinaExpertiseProcess)
    {
        abort_if(Gate::denies('oficina_expertise_process_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $oficinaExpertiseProcess->delete();

        return redirect()->route('admin.oficina-expertise-processes.index')->with('message', 'Peritagem eliminada.');
    }

    private function formData(?OficinaExpertiseProcess $process = null, ?Request $request = null): array
    {
        return [
            'process' => $process,
            'vehicles' => Vehicle::with('brand')
                ->latest()
                ->limit(800)
                ->get()
                ->mapWithKeys(fn (Vehicle $vehicle) => [$vehicle->id => trim(($vehicle->license ?: $vehicle->foreign_license ?: '#' . $vehicle->id) . ' ' . ($vehicle->brand->name ?? '') . ' ' . ($vehicle->model ?? ''))]),
            'selectedVehicleId' => $request?->integer('vehicle_id') ?: $process?->vehicle_id,
        ];
    }

    private function processData(Request $request): array
    {
        $data = $request->only([
            'vehicle_id',
            'license',
            'insurance_company',
            'claim_number',
            'process_number',
            'entry_date',
            'scheduled_expertise_date',
            'expert_name',
            'approved_amount',
            'approval_date',
            'repair_start_date',
            'expected_repair_date',
            'repair_completed_date',
            'insurance_validation_date',
            'invoice_sent_date',
            'payment_received_date',
            'status',
            'repair_type',
            'notes',
            'rejection_reason',
        ]);

        if (! empty($data['vehicle_id']) && empty($data['license'])) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $data['license'] = $vehicle?->license ?: $vehicle?->foreign_license;
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_EXPERTISE_SCHEDULED && empty($data['scheduled_expertise_date'])) {
            $data['scheduled_expertise_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_APPROVED && empty($data['approval_date'])) {
            $data['approval_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_IN_REPAIR && empty($data['repair_start_date'])) {
            $data['repair_start_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_REPAIR_COMPLETED && empty($data['repair_completed_date'])) {
            $data['repair_completed_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_INSURANCE_VALIDATION && empty($data['insurance_validation_date'])) {
            $data['insurance_validation_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_INVOICE_SENT && empty($data['invoice_sent_date'])) {
            $data['invoice_sent_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_PAYMENT_RECEIVED && empty($data['payment_received_date'])) {
            $data['payment_received_date'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === OficinaExpertiseProcess::STATUS_PAYMENT_OVERDUE && empty($data['invoice_sent_date'])) {
            $data['invoice_sent_date'] = now()->subDays(31)->toDateString();
        }

        return $data;
    }

    private function attachFiles(Request $request, OficinaExpertiseProcess $process): void
    {
        if (Gate::denies('oficina_expertise_process_attachment_create')) {
            return;
        }

        foreach (OficinaExpertiseProcess::ATTACHMENT_COLLECTIONS as $collection => $label) {
            foreach ((array) $request->file($collection, []) as $file) {
                $process->addMedia($file)->toMediaCollection($collection);
            }
        }
    }

    private function recordHistory(OficinaExpertiseProcess $process, ?string $oldStatus, ?string $newStatus, ?int $userId, ?string $notes = null): void
    {
        OficinaExpertiseProcessHistory::create([
            'process_id' => $process->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_id' => $userId,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
