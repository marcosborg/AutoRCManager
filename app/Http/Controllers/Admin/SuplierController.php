<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroySuplierRequest;
use App\Http\Requests\StoreSuplierRequest;
use App\Http\Requests\UpdateSuplierRequest;
use App\Models\Suplier;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class SuplierController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('suplier_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate(['export' => ['nullable', 'in:csv']]);

        if ($request->ajax() || $request->input('export') === 'csv') {
            $query = Suplier::query()->select(sprintf('%s.*', (new Suplier)->table));
            $table = Datatables::of($query);

            if ($request->input('export') === 'csv') {
                // Use the same searches and ordering as the visible table, without pagination.
                $table->skipPaging()->prepareQuery();
                $exportQuery = $table->getFilteredQuery();

                return response()->streamDownload(function () use ($exportQuery) {
                    $output = fopen('php://output', 'w');
                    fwrite($output, "\xEF\xBB\xBF");
                    fputcsv($output, ['ID', 'Nome'], ';');
                    foreach ($exportQuery->orderBy('supliers.id')->lazy(200) as $supplier) {
                        $name = (string) $supplier->name;
                        if (preg_match('/^[\s]*[=+@-]/u', $name)) {
                            $name = "'".$name;
                        }
                        fputcsv($output, [$supplier->id, $name], ';');
                    }
                    fclose($output);
                }, 'fornecedores-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
            }

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'suplier_show';
                $editGate      = 'suplier_edit';
                $deleteGate    = 'suplier_delete';
                $crudRoutePart = 'supliers';

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

        return view('admin.supliers.index');
    }

    public function create()
    {
        abort_if(Gate::denies('suplier_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.supliers.create');
    }

    public function store(StoreSuplierRequest $request)
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        $suplier = Suplier::create($data);

        return redirect()->route('admin.supliers.index');
    }

    public function edit(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.supliers.edit', compact('suplier'));
    }

    public function update(UpdateSuplierRequest $request, Suplier $suplier)
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        $suplier->update($data);

        return redirect()->route('admin.supliers.index');
    }

    public function show(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.supliers.show', compact('suplier'));
    }

    public function account(Suplier $suplier)
    {
        abort(Response::HTTP_GONE, 'Modulo financeiro descontinuado.');
    }

    public function destroy(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $suplier->delete();

        return back();
    }

    public function massDestroy(MassDestroySuplierRequest $request)
    {
        $supliers = Suplier::find(request('ids'));

        foreach ($supliers as $suplier) {
            $suplier->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
