<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyRepairStateRequest;
use App\Http\Requests\StoreRepairStateRequest;
use App\Http\Requests\UpdateRepairStateRequest;
use App\Models\RepairState;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class RepairStatesController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('repair_state_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = RepairState::query()->select(sprintf('%s.*', (new RepairState)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'repair_state_show';
                $editGate      = 'repair_state_edit';
                $deleteGate    = 'repair_state_delete';
                $crudRoutePart = 'repair-states';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : '';
            });

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        return view('admin.repairStates.index');
    }

    public function create()
    {
        abort_if(Gate::denies('repair_state_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.repairStates.create');
    }

    public function store(StoreRepairStateRequest $request)
    {
        $repairState = RepairState::create($request->all());

        return redirect()->route('admin.repair-states.index');
    }

    public function edit(RepairState $repairState)
    {
        abort_if(Gate::denies('repair_state_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.repairStates.edit', compact('repairState'));
    }

    public function update(UpdateRepairStateRequest $request, RepairState $repairState)
    {
        $repairState->update($request->all());

        return redirect()->route('admin.repair-states.index');
    }

    public function show(RepairState $repairState)
    {
        abort_if(Gate::denies('repair_state_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.repairStates.show', compact('repairState'));
    }

    public function destroy(RepairState $repairState)
    {
        abort_if(Gate::denies('repair_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repairState->delete();

        return back();
    }

    public function massDestroy(MassDestroyRepairStateRequest $request)
    {
        $repairStates = RepairState::find(request('ids'));

        foreach ($repairStates as $repairState) {
            $repairState->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
