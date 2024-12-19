<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyPickupStateRequest;
use App\Http\Requests\StorePickupStateRequest;
use App\Http\Requests\UpdatePickupStateRequest;
use App\Models\PickupState;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class PickupStateController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('pickup_state_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = PickupState::query()->select(sprintf('%s.*', (new PickupState)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'pickup_state_show';
                $editGate      = 'pickup_state_edit';
                $deleteGate    = 'pickup_state_delete';
                $crudRoutePart = 'pickup-states';

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

        return view('admin.pickupStates.index');
    }

    public function create()
    {
        abort_if(Gate::denies('pickup_state_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.pickupStates.create');
    }

    public function store(StorePickupStateRequest $request)
    {
        $pickupState = PickupState::create($request->all());

        return redirect()->route('admin.pickup-states.index');
    }

    public function edit(PickupState $pickupState)
    {
        abort_if(Gate::denies('pickup_state_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.pickupStates.edit', compact('pickupState'));
    }

    public function update(UpdatePickupStateRequest $request, PickupState $pickupState)
    {
        $pickupState->update($request->all());

        return redirect()->route('admin.pickup-states.index');
    }

    public function show(PickupState $pickupState)
    {
        abort_if(Gate::denies('pickup_state_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.pickupStates.show', compact('pickupState'));
    }

    public function destroy(PickupState $pickupState)
    {
        abort_if(Gate::denies('pickup_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $pickupState->delete();

        return back();
    }

    public function massDestroy(MassDestroyPickupStateRequest $request)
    {
        $pickupStates = PickupState::find(request('ids'));

        foreach ($pickupStates as $pickupState) {
            $pickupState->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
