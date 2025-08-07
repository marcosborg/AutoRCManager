<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyTimelogRequest;
use App\Http\Requests\StoreTimelogRequest;
use App\Http\Requests\UpdateTimelogRequest;
use App\Models\Timelog;
use App\Models\User;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class TimelogController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('timelog_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Timelog::with(['vehicle', 'user'])->select(sprintf('%s.*', (new Timelog)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'timelog_show';
                $editGate      = 'timelog_edit';
                $deleteGate    = 'timelog_delete';
                $crudRoutePart = 'timelogs';

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

            $table->editColumn('vehicle.model', function ($row) {
                return $row->vehicle ? (is_string($row->vehicle) ? $row->vehicle : $row->vehicle->model) : '';
            });
            $table->editColumn('vehicle.year', function ($row) {
                return $row->vehicle ? (is_string($row->vehicle) ? $row->vehicle : $row->vehicle->year) : '';
            });
            $table->editColumn('vehicle.color', function ($row) {
                return $row->vehicle ? (is_string($row->vehicle) ? $row->vehicle : $row->vehicle->color) : '';
            });
            $table->editColumn('vehicle.transmission', function ($row) {
                return $row->vehicle ? (is_string($row->vehicle) ? $row->vehicle : $row->vehicle->transmission) : '';
            });
            $table->editColumn('vehicle.month', function ($row) {
                return $row->vehicle ? (is_string($row->vehicle) ? $row->vehicle : $row->vehicle->month) : '';
            });
            $table->addColumn('user_name', function ($row) {
                return $row->user ? $row->user->name : '';
            });

            $table->editColumn('rounded_minutes', function ($row) {
                return $row->rounded_minutes ? $row->rounded_minutes : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'vehicle', 'user']);

            return $table->make(true);
        }

        return view('admin.timelogs.index');
    }

    public function create()
    {
        abort_if(Gate::denies('timelog_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.timelogs.create', compact('users', 'vehicles'));
    }

    public function store(StoreTimelogRequest $request)
    {
        $timelog = Timelog::create($request->all());

        return redirect()->route('admin.timelogs.index');
    }

    public function edit(Timelog $timelog)
    {
        abort_if(Gate::denies('timelog_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $timelog->load('vehicle', 'user');

        return view('admin.timelogs.edit', compact('timelog', 'users', 'vehicles'));
    }

    public function update(UpdateTimelogRequest $request, Timelog $timelog)
    {
        $timelog->update($request->all());

        return redirect()->route('admin.timelogs.index');
    }

    public function show(Timelog $timelog)
    {
        abort_if(Gate::denies('timelog_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $timelog->load('vehicle', 'user');

        return view('admin.timelogs.show', compact('timelog'));
    }

    public function destroy(Timelog $timelog)
    {
        abort_if(Gate::denies('timelog_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $timelog->delete();

        return back();
    }

    public function massDestroy(MassDestroyTimelogRequest $request)
    {
        $timelogs = Timelog::find(request('ids'));

        foreach ($timelogs as $timelog) {
            $timelog->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}