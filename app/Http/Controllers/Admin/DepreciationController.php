<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyDepreciationRequest;
use App\Http\Requests\StoreDepreciationRequest;
use App\Http\Requests\UpdateDepreciationRequest;
use App\Models\Depreciation;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class DepreciationController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('depreciation_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Depreciation::query()->select(sprintf('%s.*', (new Depreciation)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'depreciation_show';
                $editGate      = 'depreciation_edit';
                $deleteGate    = 'depreciation_delete';
                $crudRoutePart = 'depreciations';

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
            $table->editColumn('license_plate', function ($row) {
                return $row->license_plate ? $row->license_plate : '';
            });
            $table->editColumn('value', function ($row) {
                return $row->value ? $row->value : '';
            });

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        return view('admin.depreciations.index');
    }

    public function create()
    {
        abort_if(Gate::denies('depreciation_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.depreciations.create');
    }

    public function store(StoreDepreciationRequest $request)
    {
        $depreciation = Depreciation::create($request->all());

        return redirect()->route('admin.depreciations.index');
    }

    public function edit(Depreciation $depreciation)
    {
        abort_if(Gate::denies('depreciation_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.depreciations.edit', compact('depreciation'));
    }

    public function update(UpdateDepreciationRequest $request, Depreciation $depreciation)
    {
        $depreciation->update($request->all());

        return redirect()->route('admin.depreciations.index');
    }

    public function show(Depreciation $depreciation)
    {
        abort_if(Gate::denies('depreciation_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.depreciations.show', compact('depreciation'));
    }

    public function destroy(Depreciation $depreciation)
    {
        abort_if(Gate::denies('depreciation_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $depreciation->delete();

        return back();
    }

    public function massDestroy(MassDestroyDepreciationRequest $request)
    {
        $depreciations = Depreciation::find(request('ids'));

        foreach ($depreciations as $depreciation) {
            $depreciation->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
