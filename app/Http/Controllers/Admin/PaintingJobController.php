<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaintingJob;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PaintingJobController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('painting_job_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $query = PaintingJob::with(['vehicle.brand', 'painter'])->latest('entry_date')->latest('id');
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('painter_id'), fn ($q) => $q->where('painter_id', $request->integer('painter_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->to))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(fn ($inner) => $inner->where('license', 'like', "%{$search}%")->orWhere('brand_model', 'like', "%{$search}%"));
            });

        return view('admin.paintingJobs.index', [
            'jobs' => $query->paginate(30)->withQueryString(), 'painters' => $this->painters(), 'statuses' => PaintingJob::STATUS_SELECT,
        ]);
    }

    public function create()
    {
        abort_if(Gate::denies('painting_job_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.paintingJobs.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('painting_job_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $data = $request->validate($this->baseRules());
        $vehicle = Vehicle::with(['brand', 'client'])->findOrFail($data['vehicle_id']);
        $job = DB::transaction(function () use ($data, $vehicle, $request) {
            $client = $vehicle->client;
            $job = PaintingJob::create([
                'vehicle_id' => $vehicle->id, 'painter_id' => $data['painter_id'], 'status' => PaintingJob::STATUS_OPEN,
                'client_contact' => trim(implode(' / ', array_filter([$client?->name, $client?->phone, $client?->email]))) ?: null,
                'brand_model' => trim(implode(' ', array_filter([$vehicle->brand?->name, $vehicle->model]))) ?: null,
                'license' => $vehicle->license ?: $vehicle->foreign_license,
                'entry_date' => $data['entry_date'], 'notes' => $data['notes'] ?? null,
                'created_by_id' => $request->user()->id, 'updated_by_id' => $request->user()->id,
            ]);
            foreach (PaintingJob::DAMAGE_ZONES as $zone => $label) {
                $job->damages()->create(['zone' => $zone]);
            }
            foreach (PaintingJob::DEFAULT_MATERIALS as $position => $material) {
                $job->materials()->create(['material_type' => $material, 'position' => $position]);
            }

            return $job;
        });

        return redirect()->route('admin.painting-jobs.edit', $job)->with('message', 'Ficha de pintura criada.');
    }

    public function show(PaintingJob $paintingJob)
    {
        abort_if(Gate::denies('painting_job_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.paintingJobs.show', ['job' => $paintingJob->load($this->relations())]);
    }

    public function edit(PaintingJob $paintingJob)
    {
        abort_if(Gate::denies('painting_job_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.paintingJobs.edit', $this->formData($paintingJob));
    }

    public function update(Request $request, PaintingJob $paintingJob)
    {
        abort_if(Gate::denies('painting_job_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $data = $request->validate($this->editRules());
        DB::transaction(function () use ($paintingJob, $data, $request): void {
            $paintingJob->update([
                'painter_id' => $data['painter_id'], 'entry_date' => $data['entry_date'], 'exit_date' => $data['exit_date'] ?? null,
                'optics' => $data['optics'] ?? null, 'black_parts' => $data['black_parts'] ?? null,
                'wheels' => $data['wheels'] ?? null, 'other_work' => $data['other_work'] ?? null,
                'notes' => $data['notes'] ?? null, 'updated_by_id' => $request->user()->id,
            ]);
            $this->syncDetails($paintingJob, $data);
        });

        return redirect()->route('admin.painting-jobs.show', $paintingJob)->with('message', 'Ficha de pintura atualizada.');
    }

    public function complete(Request $request, PaintingJob $paintingJob)
    {
        abort_if(Gate::denies('painting_job_complete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $paintingJob->update([
            'status' => PaintingJob::STATUS_COMPLETED, 'exit_date' => $request->input('exit_date', now()->toDateString()),
            'completed_at' => now(), 'completed_by_id' => $request->user()->id, 'updated_by_id' => $request->user()->id,
        ]);

        return back()->with('message', 'Ficha concluída.');
    }

    public function reopen(Request $request, PaintingJob $paintingJob)
    {
        abort_if(Gate::denies('painting_job_reopen'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $paintingJob->update(['status' => PaintingJob::STATUS_OPEN, 'completed_at' => null, 'completed_by_id' => null, 'updated_by_id' => $request->user()->id]);

        return back()->with('message', 'Ficha reaberta.');
    }

    private function baseRules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'painter_id' => ['required', function ($attribute, $value, $fail) {
                if (! User::whereKey($value)->whereHas('roles', fn ($q) => $q->where('title', 'Pintor'))->exists()) {
                    $fail('O operador selecionado não tem o role Pintor.');
                }
            }],
            'entry_date' => ['required', 'date'], 'notes' => ['nullable', 'string'],
        ];
    }

    private function editRules(): array
    {
        return [
            'painter_id' => ['required', function ($attribute, $value, $fail) {
                if (! User::whereKey($value)->whereHas('roles', fn ($q) => $q->where('title', 'Pintor'))->exists()) {
                    $fail('O operador selecionado não tem o role Pintor.');
                }
            }], 'entry_date' => ['required', 'date'], 'exit_date' => ['nullable', 'date'],
            'damages' => ['required', 'array'], 'damages.*' => ['nullable', Rule::in(array_keys(PaintingJob::INTENSITY_SELECT))],
            'materials' => ['required', 'array'], 'materials.*.material_type' => ['required', 'string', 'max:191'],
            'materials.*.reference' => ['nullable', 'string', 'max:191'], 'materials.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'materials.*.used_date' => ['nullable', 'date'], 'materials.*.hours' => ['nullable', 'numeric', 'min:0'],
            'optics' => ['nullable', 'string'], 'black_parts' => ['nullable', 'string'], 'wheels' => ['nullable', 'string'],
            'other_work' => ['nullable', 'string'], 'notes' => ['nullable', 'string'],
        ];
    }

    private function syncDetails(PaintingJob $job, array $data): void
    {
        foreach (PaintingJob::DAMAGE_ZONES as $zone => $label) {
            $job->damages()->updateOrCreate(['zone' => $zone], ['intensity' => $data['damages'][$zone] ?? null]);
        }
        $job->materials()->delete();
        foreach (array_values($data['materials']) as $position => $material) {
            $job->materials()->create($material + ['position' => $position]);
        }
    }

    private function formData(?PaintingJob $job = null): array
    {
        $vehicles = Vehicle::with('brand')->whereHas('general_state', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['oficina']))
            ->orderByDesc('id')->get()->mapWithKeys(fn ($vehicle) => [$vehicle->id => trim(implode(' ', array_filter([$vehicle->license ?: $vehicle->foreign_license, $vehicle->brand?->name, $vehicle->model])))]);

        return [
            'job' => $job?->load($this->relations()), 'vehicles' => $vehicles, 'painters' => $this->painters(),
            'zones' => PaintingJob::DAMAGE_ZONES, 'intensities' => PaintingJob::INTENSITY_SELECT,
        ];
    }

    private function painters()
    {
        return User::whereHas('roles', fn ($q) => $q->where('title', 'Pintor'))->orderBy('name')->pluck('name', 'id');
    }

    private function relations(): array
    {
        return ['vehicle.brand', 'vehicle.client', 'painter', 'damages', 'materials', 'createdBy', 'updatedBy', 'completedBy'];
    }
}
