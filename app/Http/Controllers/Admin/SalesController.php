<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyVehicleRequest;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\GeneralState;
use App\Models\PaymentStatus;
use App\Models\PickupState;
use App\Models\Suplier;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class SalesController extends Controller
{

    use MediaUploadingTrait, CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Vehicle::with(['general_state', 'brand', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client'])->select(sprintf('%s.*', (new Vehicle)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'vehicle_show';
                $editGate      = 'vehicle_edit';
                $deleteGate    = 'vehicle_delete';
                $crudRoutePart = 'vehicles';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->addColumn('general_state_name', function ($row) {
                return $row->general_state ? $row->general_state->name : '';
            });

            $table->editColumn('license', function ($row) {
                return $row->license ? $row->license : '';
            });
            $table->editColumn('foreign_license', function ($row) {
                return $row->foreign_license ? $row->foreign_license : '';
            });
            $table->addColumn('brand_name', function ($row) {
                return $row->brand ? $row->brand->name : '';
            });

            $table->editColumn('model', function ($row) {
                return $row->model ? $row->model : '';
            });
            $table->editColumn('month', function ($row) {
                return $row->month ? $row->month : '';
            });
            $table->editColumn('fuel', function ($row) {
                return $row->fuel ? $row->fuel : '';
            });
            $table->editColumn('inspec_b', function ($row) {
                return $row->inspec_b ? $row->inspec_b : '';
            });
            $table->addColumn('suplier_name', function ($row) {
                return $row->suplier ? $row->suplier->name : '';
            });

            $table->editColumn('pvp', function ($row) {
                return $row->pvp ? $row->pvp : '';
            });
            $table->addColumn('client_name', function ($row) {
                return $row->client ? $row->client->name : '';
            });

            $table->editColumn('chekin_documents', function ($row) {
                return $row->chekin_documents ? $row->chekin_documents : '';
            });

            $table->editColumn('key', function ($row) {
                return $row->key ? $row->key : '';
            });
            $table->editColumn('manuals', function ($row) {
                return $row->manuals ? $row->manuals : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'general_state', 'brand', 'suplier', 'client']);

            return $table->make(true);
        }

        $general_states   = GeneralState::get();
        $brands           = Brand::get();
        $supliers         = Suplier::get();
        $payment_statuses = PaymentStatus::get();
        $carriers         = Carrier::get();
        $pickup_states    = PickupState::get();
        $clients          = Client::get();

        return view('admin.sales.index', compact('general_states', 'brands', 'supliers', 'payment_statuses', 'carriers', 'pickup_states', 'clients'));
    }

}