<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyAppreciationRequest;
use App\Http\Requests\StoreAppreciationRequest;
use App\Http\Requests\UpdateAppreciationRequest;
use App\Models\Appreciation;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AppreciationController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('appreciation_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Appreciation::query()->select(sprintf('%s.*', (new Appreciation)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'appreciation_show';
                $editGate      = 'appreciation_edit';
                $deleteGate    = 'appreciation_delete';
                $crudRoutePart = 'appreciations';

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

        return view('admin.appreciations.index');
    }

    public function create()
    {
        abort_if(Gate::denies('appreciation_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.appreciations.create');
    }

    public function store(StoreAppreciationRequest $request)
    {
        $appreciation = Appreciation::create($request->all());

        return redirect()->route('admin.appreciations.index');
    }

    public function edit(Appreciation $appreciation)
    {
        abort_if(Gate::denies('appreciation_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.appreciations.edit', compact('appreciation'));
    }

    public function update(UpdateAppreciationRequest $request, Appreciation $appreciation)
    {
        $appreciation->update($request->all());

        return redirect()->route('admin.appreciations.index');
    }

    public function show(Appreciation $appreciation)
    {
        abort_if(Gate::denies('appreciation_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.appreciations.show', compact('appreciation'));
    }

    public function destroy(Appreciation $appreciation)
    {
        abort_if(Gate::denies('appreciation_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $appreciation->delete();

        return back();
    }

    public function massDestroy(MassDestroyAppreciationRequest $request)
    {
        $appreciations = Appreciation::find(request('ids'));

        foreach ($appreciations as $appreciation) {
            $appreciation->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
