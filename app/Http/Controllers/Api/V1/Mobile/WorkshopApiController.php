<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Domain\Repairs\RepairRules;
use App\Http\Controllers\Controller;
use App\Models\Repair;
use App\Models\RepairPart;
use App\Models\RepairState;
use App\Models\RepairWorkLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkshopApiController extends Controller
{
    public function repairStates()
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json(
            RepairState::query()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function repairs(Request $request)
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = Repair::query()
            ->with(['vehicle:id,license,brand_id,model', 'vehicle.brand:id,name', 'repair_state:id,name'])
            ->whereNotNull('vehicle_id')
            ->whereHas('vehicle')
            ->orderByDesc('id');

        $status = $request->query('status');
        if ($status === 'open') {
            $query->where(function ($q) {
                $q->whereNull('repair_state_id')->orWhere('repair_state_id', '!=', 3);
            });
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->whereHas('vehicle', function ($vehicleQuery) use ($search) {
                $vehicleQuery
                    ->where('license', 'like', '%' . $search . '%')
                    ->orWhere('foreign_license', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        $repairs = $query->limit(200)->get();

        return response()->json([
            'data' => $repairs->map(fn (Repair $repair) => $this->repairListPayload($repair)),
        ]);
    }

    public function repair(Repair $repair)
    {
        abort_if(Gate::denies('repair_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repair->load([
            'vehicle:id,license,foreign_license,brand_id,model',
            'vehicle.brand:id,name',
            'repair_state:id,name',
            'parts',
            'workLogs.user:id,name',
        ]);

        return response()->json([
            'data' => $this->repairDetailPayload($repair),
        ]);
    }

    public function updateRepair(Request $request, Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'repair_state_id' => ['nullable', 'integer', 'exists:repair_states,id'],
            'work_performed' => ['nullable', 'string'],
            'materials_used' => ['nullable', 'string'],
            'expected_completion_date' => ['nullable', 'date'],
        ]);

        $repair->update($data);
        $repair->refresh();

        return response()->json([
            'data' => $this->repairDetailPayload($repair->load(['vehicle', 'repair_state', 'parts', 'workLogs.user'])),
        ]);
    }

    public function startRepair(Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (! $repair->getRawOriginal('repair_started_at')) {
            $repair->repair_started_at = now();
            $repair->repair_finished_at = null;
            $repair->save();
        }

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh(['vehicle', 'repair_state', 'parts', 'workLogs.user'])),
        ]);
    }

    public function finishRepair(Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (! $repair->getRawOriginal('repair_started_at')) {
            return response()->json([
                'message' => 'Inicie a reparação antes de finalizar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $repair->getRawOriginal('repair_finished_at')) {
            $repair->repair_finished_at = now();
            $repair->save();
        }

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh(['vehicle', 'repair_state', 'parts', 'workLogs.user'])),
        ]);
    }

    public function startWork(Repair $repair, Request $request)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $openLog = RepairWorkLog::where('repair_id', $repair->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('finished_at')
            ->first();

        if (! $openLog) {
            RepairWorkLog::create([
                'repair_id' => $repair->id,
                'user_id' => $request->user()->id,
                'started_at' => now(),
            ]);
        }

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh(['vehicle', 'repair_state', 'parts', 'workLogs.user'])),
        ]);
    }

    public function finishWork(Repair $repair, Request $request)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $openLog = RepairWorkLog::where('repair_id', $repair->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('finished_at')
            ->latest('id')
            ->first();

        if (! $openLog) {
            return response()->json([
                'message' => 'Não existe trabalho em curso para este mecânico.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $startedAt = Carbon::parse((string) $openLog->started_at);
        $endedAt = now();
        $openLog->update([
            'finished_at' => $endedAt,
            'duration_minutes' => $startedAt->diffInMinutes($endedAt),
        ]);

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh(['vehicle', 'repair_state', 'parts', 'workLogs.user'])),
        ]);
    }

    public function addPart(Request $request, Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'supplier' => ['nullable', 'string', 'max:191'],
            'invoice_number' => ['nullable', 'string', 'max:191'],
            'part_date' => ['nullable', 'date'],
            'part_name' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $repair->parts()->create($data);

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh(['vehicle', 'repair_state', 'parts', 'workLogs.user'])),
        ], Response::HTTP_CREATED);
    }

    public function deletePart(Repair $repair, RepairPart $part)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ((int) $part->repair_id !== (int) $repair->id) {
            return response()->json(['message' => 'Peça inválida para esta intervenção.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $part->delete();

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh(['vehicle', 'repair_state', 'parts', 'workLogs.user'])),
        ]);
    }

    public function vehicles(Request $request)
    {
        abort_if(Gate::denies('repair_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $search = trim((string) $request->query('search', ''));
        $query = Vehicle::query()->with('brand:id,name')->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('license', 'like', '%' . $search . '%')
                    ->orWhere('foreign_license', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        $vehicles = $query->limit(50)->get(['id', 'license', 'foreign_license', 'brand_id', 'model']);

        return response()->json([
            'data' => $vehicles->map(function (Vehicle $vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license' => $vehicle->license,
                    'foreign_license' => $vehicle->foreign_license,
                    'brand' => $vehicle->brand?->name,
                    'model' => $vehicle->model,
                ];
            }),
        ]);
    }

    public function newIntervention(Request $request, Vehicle $vehicle)
    {
        abort_if(Gate::denies('repair_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (RepairRules::hasOpenRepairs($vehicle->id)) {
            return response()->json([
                'message' => 'Já existe uma intervenção aberta para esta viatura.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $repair = Repair::create([
            'vehicle_id' => $vehicle->id,
            'kilometers' => is_numeric($vehicle->kilometers) ? (int) $vehicle->kilometers : null,
            'timestamp' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $repair->id,
                'vehicle_id' => $repair->vehicle_id,
                'timestamp' => $repair->getRawOriginal('timestamp'),
            ],
        ], Response::HTTP_CREATED);
    }

    private function repairListPayload(Repair $repair): array
    {
        $startedAt = $repair->getRawOriginal('repair_started_at');
        $finishedAt = $repair->getRawOriginal('repair_finished_at');

        return [
            'id' => $repair->id,
            'vehicle_id' => $repair->vehicle_id,
            'vehicle_label' => trim(implode(' ', array_filter([
                $repair->vehicle?->license ?? $repair->vehicle?->foreign_license,
                $repair->vehicle?->brand?->name,
                $repair->vehicle?->model,
            ]))),
            'state' => $repair->repair_state?->name ?? 'Aberta',
            'repair_state_id' => $repair->repair_state_id,
            'is_open' => $repair->repair_state_id === null || (int) $repair->repair_state_id !== 3,
            'timestamp' => $repair->getRawOriginal('timestamp'),
            'repair_started_at' => $startedAt,
            'repair_finished_at' => $finishedAt,
            'repair_duration_minutes' => $repair->repair_duration_minutes,
        ];
    }

    private function repairDetailPayload(Repair $repair): array
    {
        $repair->loadMissing(['vehicle.brand', 'repair_state', 'parts', 'workLogs.user']);

        $totalPartsAmount = (float) $repair->parts->sum('amount');
        $totalWorkMinutes = (int) $repair->workLogs->sum('duration_minutes');

        return [
            'id' => $repair->id,
            'vehicle' => [
                'id' => $repair->vehicle?->id,
                'license' => $repair->vehicle?->license,
                'foreign_license' => $repair->vehicle?->foreign_license,
                'brand' => $repair->vehicle?->brand?->name,
                'model' => $repair->vehicle?->model,
                'initial_photos' => $repair->vehicle
                    ? $repair->vehicle->inicial->map(fn ($media) => [
                        'id' => $media->id,
                        'url' => url($media->getUrl()),
                        'thumb' => url($media->getUrl('thumb')),
                    ])->values()
                    : [],
            ],
            'repair_state_id' => $repair->repair_state_id,
            'repair_state' => $repair->repair_state?->name ?? 'Aberta',
            'timestamp' => $repair->getRawOriginal('timestamp'),
            'repair_started_at' => $repair->getRawOriginal('repair_started_at'),
            'repair_finished_at' => $repair->getRawOriginal('repair_finished_at'),
            'repair_duration_minutes' => $repair->repair_duration_minutes,
            'work_performed' => $repair->work_performed,
            'materials_used' => $repair->materials_used,
            'expected_completion_date' => $repair->getRawOriginal('expected_completion_date'),
            'parts' => $repair->parts
                ->sortByDesc('id')
                ->values()
                ->map(fn (RepairPart $part) => [
                    'id' => $part->id,
                    'supplier' => $part->supplier,
                    'invoice_number' => $part->invoice_number,
                    'part_date' => $part->getRawOriginal('part_date'),
                    'part_name' => $part->part_name,
                    'amount' => (float) $part->amount,
                ]),
            'parts_total' => $totalPartsAmount,
            'work_logs' => $repair->workLogs
                ->sortByDesc('id')
                ->values()
                ->map(fn (RepairWorkLog $log) => [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user?->name,
                    'started_at' => $log->getRawOriginal('started_at'),
                    'finished_at' => $log->getRawOriginal('finished_at'),
                    'duration_minutes' => (int) ($log->duration_minutes ?? 0),
                ]),
            'work_total_minutes' => $totalWorkMinutes,
        ];
    }
}
