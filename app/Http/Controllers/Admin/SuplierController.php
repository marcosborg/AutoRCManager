<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroySuplierRequest;
use App\Http\Requests\StoreSuplierRequest;
use App\Http\Requests\UpdateSuplierRequest;
use App\Models\Suplier;
use App\Models\Vehicle;
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

        if ($request->ajax()) {
            $query = Suplier::query()->select(sprintf('%s.*', (new Suplier)->table));
            $table = Datatables::of($query);

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
        $suplier = Suplier::create($request->all());

        return redirect()->route('admin.supliers.index');
    }

    public function edit(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.supliers.edit', compact('suplier'));
    }

    public function update(UpdateSuplierRequest $request, Suplier $suplier)
    {
        $suplier->update($request->all());

        return redirect()->route('admin.supliers.index');
    }

    public function show(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.supliers.show', compact('suplier'));
    }

    public function account(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $suplier->load([
            'vehicles.brand',
            'vehicles.general_state',
            'vehicles.acquisition_operations.account_item.account_category',
        ]);

        $vehicleBreakdown = $suplier->vehicles->map(function (Vehicle $vehicle) {
            $purchasePrice = (float) ($vehicle->purchase_price ?? 0);
            $paid = (float) ($vehicle->acquisition_operations->sum('total') ?? 0);

            return [
                'vehicle' => $vehicle,
                'purchase_price' => $purchasePrice,
                'paid' => $paid,
                'balance' => $purchasePrice - $paid,
            ];
        });

        $summary = [
            'vehicles' => $vehicleBreakdown->count(),
            'purchase_total' => (float) $vehicleBreakdown->sum('purchase_price'),
            'paid_total' => (float) $vehicleBreakdown->sum('paid'),
            'balance_total' => (float) $vehicleBreakdown->sum('balance'),
        ];

        return view('admin.supliers.account', compact('suplier', 'vehicleBreakdown', 'summary'));
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
