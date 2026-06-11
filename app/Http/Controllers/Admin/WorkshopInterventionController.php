<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkshopInterventionRequest;
use App\Http\Requests\UpdateWorkshopInterventionRequest;
use App\Models\Repair;
use App\Models\User;
use App\Models\WorkshopIntervention;
use App\Models\WorkshopInterventionType;
use App\Services\WorkshopInterventionExecutionService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkshopInterventionController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('workshop_planning_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $start = $request->input('start_date', now()->startOfWeek()->toDateString());
        $end = $request->input('end_date', now()->endOfWeek()->toDateString());

        $query = WorkshopIntervention::with(['repair.vehicle', 'type', 'mechanics', 'workLogs'])
            ->whereDate('planned_start_date', '<=', $end)
            ->whereDate('planned_end_date', '>=', $start)
            ->orderBy('planned_start_date')
            ->orderBy('id');

        $query->when($request->filled('mechanic_id'), fn ($q) => $q->whereHas('mechanics', fn ($mechanics) => $mechanics->whereKey($request->integer('mechanic_id'))));
        $query->when($request->filled('type_id'), fn ($q) => $q->where('type_id', $request->integer('type_id')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        return view('admin.workshopInterventions.index', [
            'interventions' => $query->paginate(40)->withQueryString(),
            'mechanics' => $this->mechanics(),
            'types' => WorkshopInterventionType::orderBy('name')->pluck('name', 'id'),
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('workshop_planning_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.workshopInterventions.create', $this->formData(null, $request));
    }

    public function store(StoreWorkshopInterventionRequest $request)
    {
        $data = $request->safe()->except('mechanic_ids');
        $data['created_by_id'] = auth()->id();
        $data['status'] = 'planned';
        $intervention = WorkshopIntervention::create($data);
        $intervention->mechanics()->sync($request->input('mechanic_ids'));

        return redirect()->route('admin.workshop-interventions.index')->with('message', 'Trabalho planeado com sucesso.');
    }

    public function edit(WorkshopIntervention $workshopIntervention)
    {
        abort_if(Gate::denies('workshop_planning_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.workshopInterventions.edit', $this->formData($workshopIntervention));
    }

    public function update(UpdateWorkshopInterventionRequest $request, WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        $data = $request->safe()->except('mechanic_ids');
        if (($data['status'] ?? null) === 'completed' && $workshopIntervention->status !== 'completed') {
            return back()->withErrors(['status' => 'Conclua o trabalho através da ação Concluir.'])->withInput();
        }
        if ($workshopIntervention->status === 'completed' && ($data['status'] ?? null) !== 'completed') {
            return back()->withErrors(['status' => 'Um trabalho concluído não pode ser reaberto por edição.'])->withInput();
        }

        $removedMechanics = $workshopIntervention->mechanics()->pluck('users.id')->diff($request->input('mechanic_ids'));
        if ($workshopIntervention->workLogs()->whereIn('user_id', $removedMechanics)->whereNull('finished_at')->exists()) {
            return back()->withErrors(['mechanic_ids' => 'Termine os cronómetros em curso antes de retirar mecânicos.'])->withInput();
        }

        $workshopIntervention->update($data);
        $workshopIntervention->mechanics()->sync($request->input('mechanic_ids'));
        if (($data['status'] ?? null) === 'cancelled') {
            $service->cancel($workshopIntervention);
        }

        return redirect()->route('admin.workshop-interventions.index')->with('message', 'Trabalho atualizado com sucesso.');
    }

    public function destroy(WorkshopIntervention $workshopIntervention)
    {
        abort_if(Gate::denies('workshop_planning_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if($workshopIntervention->workLogs()->exists(), Response::HTTP_UNPROCESSABLE_ENTITY, 'Não é possível eliminar um trabalho com tempos registados. Cancele-o.');
        $workshopIntervention->delete();

        return back()->with('message', 'Trabalho eliminado com sucesso.');
    }

    public function start(WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        abort_if(Gate::denies('workshop_task_execute'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $service->start($workshopIntervention, auth()->user());

        return back()->with('message', 'Trabalho iniciado.');
    }

    public function finish(WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        abort_if(Gate::denies('workshop_task_execute'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $service->finish($workshopIntervention, auth()->user());

        return back()->with('message', 'Tempo de trabalho terminado.');
    }

    public function complete(WorkshopIntervention $workshopIntervention, WorkshopInterventionExecutionService $service)
    {
        abort_if(Gate::denies('workshop_task_execute'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $service->complete($workshopIntervention, auth()->user());

        return back()->with('message', 'Trabalho concluído.');
    }

    private function formData(?WorkshopIntervention $intervention = null, ?Request $request = null): array
    {
        $repairs = Repair::with('vehicle')->latest()->limit(500)->get()->mapWithKeys(function (Repair $repair) {
            $vehicle = $repair->vehicle;
            $label = ($vehicle?->license ?: $vehicle?->foreign_license ?: 'Sem matrícula').' - Intervenção #'.$repair->id;

            return [$repair->id => $label];
        });

        $types = WorkshopInterventionType::query()
            ->where(function ($query) use ($intervention) {
                $query->where('is_active', true);
                if ($intervention?->type_id) {
                    $query->orWhere('id', $intervention->type_id);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'intervention' => $intervention?->load('mechanics'),
            'repairs' => $repairs,
            'types' => $types,
            'mechanics' => $this->mechanics(),
            'selectedRepairId' => $request?->integer('repair_id') ?: $intervention?->repair_id,
        ];
    }

    private function mechanics()
    {
        return User::whereHas('roles', fn ($query) => $query->whereIn('title', ['Mecânico', 'Mecanico']))
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
