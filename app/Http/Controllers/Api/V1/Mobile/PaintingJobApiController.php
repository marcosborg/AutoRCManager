<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PaintingJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PaintingJobApiController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('painting_job_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $query = PaintingJob::with(['vehicle.brand', 'painter'])->latest('entry_date')->latest('id');
        if (Gate::denies('painting_job_create')) {
            $query->where('painter_id', $request->user()->id);
        }
        if ($request->filled('status')) {
            $request->validate(['status' => [Rule::in(array_keys(PaintingJob::STATUS_SELECT))]]);
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->get()->map(fn (PaintingJob $job) => $this->summary($job))]);
    }

    public function show(Request $request, PaintingJob $paintingJob)
    {
        $this->authorizeJob($request, $paintingJob, 'painting_job_show');

        return response()->json(['data' => $this->payload($paintingJob)]);
    }

    public function update(Request $request, PaintingJob $paintingJob)
    {
        $this->authorizeJob($request, $paintingJob, 'painting_job_edit');
        if ($paintingJob->status === PaintingJob::STATUS_COMPLETED && Gate::denies('painting_job_reopen')) {
            return response()->json(['message' => 'Uma ficha concluída já não pode ser alterada.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = $request->validate($this->rules());
        DB::transaction(function () use ($paintingJob, $data, $request): void {
            $paintingJob->update([
                'optics' => $data['optics'] ?? null,
                'black_parts' => $data['black_parts'] ?? null,
                'wheels' => $data['wheels'] ?? null,
                'other_work' => $data['other_work'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_by_id' => $request->user()->id,
            ]);
            $this->syncDetails($paintingJob, $data);
        });

        return response()->json(['data' => $this->payload($paintingJob->fresh())]);
    }

    public function complete(Request $request, PaintingJob $paintingJob)
    {
        $this->authorizeJob($request, $paintingJob, 'painting_job_complete');
        if ($paintingJob->status === PaintingJob::STATUS_COMPLETED) {
            return response()->json(['data' => $this->payload($paintingJob)]);
        }
        $data = $request->validate($this->rules());
        DB::transaction(function () use ($paintingJob, $data, $request): void {
            $paintingJob->update([
                'status' => PaintingJob::STATUS_COMPLETED,
                'exit_date' => now()->toDateString(),
                'completed_at' => now(),
                'completed_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
                'optics' => $data['optics'] ?? null,
                'black_parts' => $data['black_parts'] ?? null,
                'wheels' => $data['wheels'] ?? null,
                'other_work' => $data['other_work'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $this->syncDetails($paintingJob, $data);
        });

        return response()->json(['data' => $this->payload($paintingJob->fresh())]);
    }

    private function authorizeJob(Request $request, PaintingJob $job, string $permission): void
    {
        abort_if(Gate::denies($permission), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if (Gate::denies('painting_job_create') && (int) $job->painter_id !== (int) $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }
    }

    private function rules(): array
    {
        return [
            'damages' => ['required', 'array'],
            'damages.*.zone' => ['required', Rule::in(array_keys(PaintingJob::DAMAGE_ZONES))],
            'damages.*.intensity' => ['nullable', Rule::in(array_keys(PaintingJob::INTENSITY_SELECT))],
            'materials' => ['required', 'array'],
            'materials.*.material_type' => ['required', 'string', 'max:191'],
            'materials.*.reference' => ['nullable', 'string', 'max:191'],
            'materials.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'materials.*.used_date' => ['nullable', 'date'],
            'materials.*.hours' => ['nullable', 'numeric', 'min:0'],
            'optics' => ['nullable', 'string'], 'black_parts' => ['nullable', 'string'],
            'wheels' => ['nullable', 'string'], 'other_work' => ['nullable', 'string'], 'notes' => ['nullable', 'string'],
        ];
    }

    private function syncDetails(PaintingJob $job, array $data): void
    {
        $damages = collect($data['damages'])->keyBy('zone');
        foreach (PaintingJob::DAMAGE_ZONES as $zone => $label) {
            $job->damages()->updateOrCreate(['zone' => $zone], ['intensity' => $damages->get($zone)['intensity'] ?? null]);
        }
        $job->materials()->delete();
        foreach (array_values($data['materials']) as $position => $material) {
            $job->materials()->create($material + ['position' => $position]);
        }
    }

    private function summary(PaintingJob $job): array
    {
        return [
            'id' => $job->id, 'status' => $job->status, 'status_label' => PaintingJob::STATUS_SELECT[$job->status],
            'license' => $job->license, 'brand_model' => $job->brand_model,
            'entry_date' => $job->entry_date?->format('Y-m-d'), 'exit_date' => $job->exit_date?->format('Y-m-d'),
            'painter' => $job->painter ? ['id' => $job->painter->id, 'name' => $job->painter->name] : null,
        ];
    }

    private function payload(PaintingJob $job): array
    {
        $job->loadMissing(['vehicle.brand', 'painter', 'damages', 'materials']);
        $intensities = $job->damages->keyBy('zone');

        return $this->summary($job) + [
            'vehicle' => ['id' => $job->vehicle_id, 'license' => $job->license, 'brand_model' => $job->brand_model],
            'client_contact' => $job->client_contact,
            'operator' => $job->painter?->name,
            'damages' => collect(PaintingJob::DAMAGE_ZONES)->map(fn ($label, $zone) => [
                'zone' => $zone, 'label' => $label, 'intensity' => $intensities->get($zone)?->intensity,
            ])->values(),
            'intensity_options' => PaintingJob::INTENSITY_SELECT,
            'materials' => $job->materials->map(fn ($item) => [
                'id' => $item->id, 'material_type' => $item->material_type, 'reference' => $item->reference,
                'quantity' => $item->quantity !== null ? (float) $item->quantity : null,
                'used_date' => $item->used_date?->format('Y-m-d'), 'hours' => $item->hours !== null ? (float) $item->hours : null,
            ])->values(),
            'optics' => $job->optics, 'black_parts' => $job->black_parts, 'wheels' => $job->wheels,
            'other_work' => $job->other_work, 'notes' => $job->notes,
            'completed_at' => $job->completed_at?->toIso8601String(),
        ];
    }
}
