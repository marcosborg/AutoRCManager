<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyCarrierRequest;
use App\Http\Requests\StoreCarrierRequest;
use App\Http\Requests\UpdateCarrierRequest;
use App\Models\Carrier;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CarrierController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('carrier_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Carrier::query()->select(sprintf('%s.*', (new Carrier)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'carrier_show';
                $editGate      = 'carrier_edit';
                $deleteGate    = 'carrier_delete';
                $crudRoutePart = 'carriers';

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

        return view('admin.carriers.index');
    }

    public function create()
    {
        abort_if(Gate::denies('carrier_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.carriers.create');
    }

    public function store(StoreCarrierRequest $request)
    {
        $carrier = Carrier::create($request->all());

        return redirect()->route('admin.carriers.index');
    }

    public function edit(Carrier $carrier)
    {
        abort_if(Gate::denies('carrier_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.carriers.edit', compact('carrier'));
    }

    public function update(UpdateCarrierRequest $request, Carrier $carrier)
    {
        $carrier->update($request->all());

        return redirect()->route('admin.carriers.index');
    }

    public function show(Carrier $carrier)
    {
        abort_if(Gate::denies('carrier_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.carriers.show', compact('carrier'));
    }

    public function destroy(Carrier $carrier)
    {
        abort_if(Gate::denies('carrier_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $carrier->delete();

        return back();
    }

    public function massDestroy(MassDestroyCarrierRequest $request)
    {
        $carriers = Carrier::find(request('ids'));

        foreach ($carriers as $carrier) {
            $carrier->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
