<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkshopInterventionType;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class WorkshopInterventionTypeController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('workshop_intervention_type_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.workshopInterventionTypes.index', ['types' => WorkshopInterventionType::withCount('interventions')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('workshop_intervention_type_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        WorkshopInterventionType::create($request->validate(['name' => ['required', 'string', 'max:191', 'unique:workshop_intervention_types,name']]));

        return back()->with('message', 'Tipo criado com sucesso.');
    }

    public function update(Request $request, WorkshopInterventionType $workshopInterventionType)
    {
        abort_if(Gate::denies('workshop_intervention_type_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', Rule::unique('workshop_intervention_types', 'name')->ignore($workshopInterventionType)],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $workshopInterventionType->update($data);

        return back()->with('message', 'Tipo atualizado com sucesso.');
    }

    public function destroy(WorkshopInterventionType $workshopInterventionType)
    {
        abort_if(Gate::denies('workshop_intervention_type_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($workshopInterventionType->interventions()->exists()) {
            return back()->withErrors(['type' => 'Este tipo já foi utilizado e não pode ser eliminado. Pode desativá-lo.']);
        }
        $workshopInterventionType->delete();

        return back()->with('message', 'Tipo eliminado com sucesso.');
    }
}
