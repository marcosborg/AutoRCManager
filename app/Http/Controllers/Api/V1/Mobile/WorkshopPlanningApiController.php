<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkshopInterventionRequest;
use App\Http\Requests\UpdateWorkshopInterventionRequest;
use App\Models\RepairWorkLog;
use App\Models\User;
use App\Models\WorkshopIntervention;
use App\Models\WorkshopInterventionType;
use App\Services\WorkshopInterventionExecutionService;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class WorkshopPlanningApiController extends Controller
{
    public function myAgenda(Request $request)
    {
        abort_if(Gate::denies('workshop_task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $start = $request->input('start_date', now()->startOfWeek()->toDateString());
        $end = $request->input('end_date', now()->endOfWeek()->toDateString());

        $items = WorkshopIntervention::with($this->relations())
            ->whereHas('mechanics', fn ($query) => $query->whereKey($request->user()->id))
            ->whereDate('planned_start_date', '<=', $end)
            ->whereDate('planned_end_date', '>=', $start)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderBy('planned_start_date')->orderBy('id')->get();

        return response()->json(['data' => $items->map(fn ($item) => $this->payload($item, $request->user()))]);
    }

    public function show(Request $request, WorkshopIntervention $workshopIntervention)
    {
        abort_if(Gate::denies('workshop_task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_unless($this->canView($workshopIntervention, $request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json(['data' => $this->payload($workshopIntervention->load($this->relations()), $request->user())]);
    }

    public function start(Request $request, WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        abort_if(Gate::denies('workshop_task_execute'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $service->start($workshopIntervention, $request->user());

        return response()->json(['data' => $this->payload($workshopIntervention->fresh($this->relations()), $request->user())]);
    }

    public function finish(Request $request, WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        abort_if(Gate::denies('workshop_task_execute'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $service->finish($workshopIntervention, $request->user());

        return response()->json(['data' => $this->payload($workshopIntervention->fresh($this->relations()), $request->user())]);
    }

    public function complete(Request $request, WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        abort_if(Gate::denies('workshop_task_execute'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $service->complete($workshopIntervention, $request->user());

        return response()->json(['data' => $this->payload($workshopIntervention->fresh($this->relations()), $request->user())]);
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('workshop_planning_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $items = WorkshopIntervention::with($this->relations())
            ->when($request->filled('repair_id'), fn ($q) => $q->where('repair_id', $request->integer('repair_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(50);

        return response()->json($items->through(fn ($item) => $this->payload($item, $request->user())));
    }

    public function store(StoreWorkshopInterventionRequest $request)
    {
        $data = $request->safe()->except('mechanic_ids');
        $data['created_by_id'] = $request->user()->id;
        $data['status'] = 'planned';
        $item = WorkshopIntervention::create($data);
        $item->mechanics()->sync($request->input('mechanic_ids'));

        return response()->json(['data' => $this->payload($item->load($this->relations()), $request->user())], Response::HTTP_CREATED);
    }

    public function update(UpdateWorkshopInterventionRequest $request, WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        $data = $request->validated();
        $mechanicIds = $data['mechanic_ids'];
        unset($data['mechanic_ids']);
        if (($data['status'] ?? null) === 'completed' && $workshopIntervention->status !== 'completed') {
            return response()->json(['message' => 'Conclua o trabalho através da ação de conclusão.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($workshopIntervention->status === 'completed' && ($data['status'] ?? null) !== 'completed') {
            return response()->json(['message' => 'Um trabalho concluído não pode ser reaberto por edição.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $removedMechanics = $workshopIntervention->mechanics()->pluck('users.id')->diff($mechanicIds);
        if ($workshopIntervention->workLogs()->whereIn('user_id', $removedMechanics)->whereNull('finished_at')->exists()) {
            return response()->json(['message' => 'Termine os cronómetros em curso antes de retirar mecânicos.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $workshopIntervention->update($data);
        $workshopIntervention->mechanics()->sync($mechanicIds);
        if (($data['status'] ?? null) === 'cancelled') {
            $service->cancel($workshopIntervention);
        }

        return response()->json(['data' => $this->payload($workshopIntervention->fresh($this->relations()), $request->user())]);
    }

    public function types(Request $request)
    {
        abort_if(Gate::denies('workshop_task_access') && Gate::denies('workshop_planning_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json(['data' => WorkshopInterventionType::orderBy('name')->get(['id', 'name', 'is_active'])]);
    }

    public function mechanics()
    {
        abort_if(Gate::denies('workshop_planning_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $mechanics = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('title', ['Mecânico', 'Mecanico']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $mechanics]);
    }

    public function storeType(Request $request)
    {
        abort_if(Gate::denies('workshop_intervention_type_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $type = WorkshopInterventionType::create($request->validate(['name' => ['required', 'string', 'max:191', 'unique:workshop_intervention_types,name']]));

        return response()->json(['data' => $type], Response::HTTP_CREATED);
    }

    public function updateType(Request $request, WorkshopInterventionType $workshopInterventionType)
    {
        abort_if(Gate::denies('workshop_intervention_type_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', Rule::unique('workshop_intervention_types', 'name')->ignore($workshopInterventionType)],
            'is_active' => ['required', 'boolean'],
        ]);
        $workshopInterventionType->update($data);

        return response()->json(['data' => $workshopInterventionType->fresh()]);
    }

    public function destroyType(WorkshopInterventionType $workshopInterventionType)
    {
        abort_if(Gate::denies('workshop_intervention_type_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($workshopInterventionType->interventions()->exists()) {
            return response()->json(['message' => 'Este tipo já foi utilizado e não pode ser eliminado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $workshopInterventionType->delete();

        return response()->noContent();
    }

    public function destroy(WorkshopIntervention $workshopIntervention)
    {
        abort_if(Gate::denies('workshop_planning_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($workshopIntervention->workLogs()->exists()) {
            return response()->json(['message' => 'Não é possível eliminar um trabalho com tempos registados.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $workshopIntervention->delete();

        return response()->noContent();
    }

    private function canView(WorkshopIntervention $item, User $user): bool
    {
        return Gate::allows('workshop_planning_access') || $item->mechanics()->whereKey($user->id)->exists();
    }

    private function relations(): array
    {
        return ['repair.vehicle:id,license,foreign_license', 'type:id,name,is_active', 'mechanics:id,name', 'workLogs.user:id,name'];
    }

    private function payload(WorkshopIntervention $item, User $user): array
    {
        $logs = $item->workLogs;

        return [
            'id' => $item->id, 'repair_id' => $item->repair_id,
            'vehicle' => ['id' => $item->repair?->vehicle?->id, 'license' => $item->repair?->vehicle?->license ?: $item->repair?->vehicle?->foreign_license],
            'type' => $item->type ? ['id' => $item->type->id, 'name' => $item->type->name] : null,
            'title' => $item->title, 'description' => $item->description,
            'planned_start_date' => $item->planned_start_date?->format('Y-m-d'), 'planned_end_date' => $item->planned_end_date?->format('Y-m-d'),
            'status' => $item->status, 'status_label' => WorkshopIntervention::STATUS_SELECT[$item->status] ?? $item->status,
            'mechanics' => $item->mechanics->map(fn ($mechanic) => ['id' => $mechanic->id, 'name' => $mechanic->name])->values(),
            'active_mechanics' => $logs
                ->whereNull('finished_at')
                ->unique('user_id')
                ->map(fn (RepairWorkLog $log) => ['id' => (int) $log->user_id, 'name' => $log->user?->name ?? 'Desconhecido'])
                ->values(),
            'work_logs' => $logs->map(fn (RepairWorkLog $log) => ['id' => $log->id, 'user_id' => $log->user_id, 'user_name' => $log->user?->name, 'started_at' => $log->getRawOriginal('started_at'), 'finished_at' => $log->getRawOriginal('finished_at'), 'duration_minutes' => $log->duration_minutes])->values(),
            'my_work_in_progress' => $logs->contains(fn ($log) => (int) $log->user_id === (int) $user->id && ! $log->finished_at),
            'completed_at' => $item->completed_at?->toIso8601String(),
        ];
    }
}
