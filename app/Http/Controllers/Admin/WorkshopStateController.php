<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkshopStateRequest;
use App\Http\Requests\UpdateWorkshopStateRequest;
use App\Models\WorkshopState;
use Gate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WorkshopStateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(Gate::denies('workshop_state_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $workshopStates = WorkshopState::query()
            ->withCount('vehicles')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('admin.workshopStates.index', compact('workshopStates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('admin.workshop-states.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkshopStateRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        DB::transaction(function () use ($data): void {
            if ($data['is_default']) {
                WorkshopState::query()->update(['is_default' => false]);
                $data['is_active'] = true;
            }

            WorkshopState::create($data);
        });

        return redirect()->route('admin.workshop-states.index')->with('message', 'Estado da oficina criado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkshopState $workshopState)
    {
        return redirect()->route('admin.workshop-states.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkshopState $workshopState)
    {
        return redirect()->route('admin.workshop-states.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkshopStateRequest $request, WorkshopState $workshopState)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($workshopState->is_default && (! $data['is_active'] || ! $data['is_default'])) {
            return back()->withErrors(['workshop_state' => 'O estado predefinido não pode ser desativado nem deixar de ser predefinido.']);
        }

        DB::transaction(function () use ($data, $workshopState): void {
            if ($data['is_default']) {
                WorkshopState::query()->where('id', '!=', $workshopState->id)->update(['is_default' => false]);
                $data['is_active'] = true;
            }

            $workshopState->update($data);
        });

        return redirect()->route('admin.workshop-states.index')->with('message', 'Estado da oficina atualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkshopState $workshopState)
    {
        abort_if(Gate::denies('workshop_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($workshopState->is_default) {
            return back()->withErrors(['workshop_state' => 'O estado predefinido não pode ser eliminado.']);
        }

        if ($workshopState->vehicles()->exists()) {
            $workshopState->update(['is_active' => false]);

            return back()->with('message', 'O estado está em utilização e foi desativado.');
        }

        $workshopState->delete();

        return back()->with('message', 'Estado da oficina eliminado.');
    }
}
