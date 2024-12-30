<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyRepairRequest;
use App\Http\Requests\StoreRepairRequest;
use App\Http\Requests\UpdateRepairRequest;
use App\Models\Repair;
use App\Models\RepairState;
use App\Models\User;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class RepairController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Repair::with(['vehicle', 'user', 'repair_state'])->select(sprintf('%s.*', (new Repair)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'repair_show';
                $editGate      = 'repair_edit';
                $deleteGate    = 'repair_delete';
                $crudRoutePart = 'repairs';

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
            $table->addColumn('vehicle_license', function ($row) {
                return $row->vehicle ? $row->vehicle->license : '';
            });

            $table->editColumn('obs_1', function ($row) {
                return $row->obs_1 ? $row->obs_1 : '';
            });
            $table->addColumn('user_name', function ($row) {
                return $row->user ? $row->user->name : '';
            });

            $table->addColumn('repair_state_name', function ($row) {
                return $row->repair_state ? $row->repair_state->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'vehicle', 'user', 'repair_state']);

            return $table->make(true);
        }

        $vehicles      = Vehicle::get();
        $users         = User::get();
        $repair_states = RepairState::get();

        return view('admin.repairs.index', compact('vehicles', 'users', 'repair_states'));
    }

    public function create()
    {
        abort_if(Gate::denies('repair_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $repair_states = RepairState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.repairs.create', compact('repair_states', 'users', 'vehicles'));
    }

    public function store(StoreRepairRequest $request)
    {
        $repair = Repair::create($request->all());

        return redirect()->route('admin.repairs.index');
    }

    public function edit(Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $repair_states = RepairState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $repair->load('vehicle', 'user', 'repair_state');

        return view('admin.repairs.edit', compact('repair', 'repair_states', 'users', 'vehicles'));
    }

    public function update(UpdateRepairRequest $request, Repair $repair)
    {
        $repair->update($request->all());

        return redirect()->route('admin.repairs.index');
    }

    public function show(Repair $repair)
    {
        abort_if(Gate::denies('repair_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repair->load('vehicle', 'user', 'repair_state');

        return view('admin.repairs.show', compact('repair'));
    }

    public function destroy(Repair $repair)
    {
        abort_if(Gate::denies('repair_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repair->delete();

        return back();
    }

    public function massDestroy(MassDestroyRepairRequest $request)
    {
        $repairs = Repair::find(request('ids'));

        foreach ($repairs as $repair) {
            $repair->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
