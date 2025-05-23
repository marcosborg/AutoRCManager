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
use App\Models\PaymentStatus;
use App\Models\PickupState;
use App\Models\Suplier;
use App\Models\Vehicle;
use App\Models\GeneralState;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class VehicleController extends Controller
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

        return view('admin.vehicles.index', compact('general_states', 'brands', 'supliers', 'payment_statuses', 'carriers', 'pickup_states', 'clients'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $general_states = GeneralState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_statuses = PaymentStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carriers = Carrier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $pickup_states = PickupState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicles.create', compact('general_states', 'brands', 'carriers', 'clients', 'payment_statuses', 'pickup_states', 'supliers'));
    }

    public function store(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::create($request->all());

        foreach ($request->input('documents', []) as $file) {
            $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
        }

        foreach ($request->input('photos', []) as $file) {
            $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('photos');
        }

        foreach ($request->input('invoice', []) as $file) {
            $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('invoice');
        }

        foreach ($request->input('inicial', []) as $file) {
            $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('inicial');
        }

        foreach ($request->input('withdrawal_authorization_file', []) as $file) {
            $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('withdrawal_authorization_file');
        }

        foreach ($request->input('withdrawal_documents', []) as $file) {
            $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('withdrawal_documents');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $vehicle->id]);
        }

        return redirect()->route('admin.vehicles.index');
    }

    public function edit(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $general_states = GeneralState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_statuses = PaymentStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carriers = Carrier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $pickup_states = PickupState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle->load('brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client');

        return view('admin.vehicles.edit', compact('general_states', 'brands', 'carriers', 'clients', 'payment_statuses', 'pickup_states', 'supliers', 'vehicle'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->update($request->all());

        if (count($vehicle->documents) > 0) {
            foreach ($vehicle->documents as $media) {
                if (! in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
            }
        }

        if (count($vehicle->photos) > 0) {
            foreach ($vehicle->photos as $media) {
                if (! in_array($media->file_name, $request->input('photos', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->photos->pluck('file_name')->toArray();
        foreach ($request->input('photos', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('photos');
            }
        }

        if (count($vehicle->invoice) > 0) {
            foreach ($vehicle->invoice as $media) {
                if (! in_array($media->file_name, $request->input('invoice', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->invoice->pluck('file_name')->toArray();
        foreach ($request->input('invoice', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('invoice');
            }
        }

        if (count($vehicle->inicial) > 0) {
            foreach ($vehicle->inicial as $media) {
                if (! in_array($media->file_name, $request->input('inicial', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->inicial->pluck('file_name')->toArray();
        foreach ($request->input('inicial', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('inicial');
            }
        }

        if (count($vehicle->withdrawal_authorization_file) > 0) {
            foreach ($vehicle->withdrawal_authorization_file as $media) {
                if (! in_array($media->file_name, $request->input('withdrawal_authorization_file', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->withdrawal_authorization_file->pluck('file_name')->toArray();
        foreach ($request->input('withdrawal_authorization_file', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('withdrawal_authorization_file');
            }
        }

        if (count($vehicle->withdrawal_documents) > 0) {
            foreach ($vehicle->withdrawal_documents as $media) {
                if (! in_array($media->file_name, $request->input('withdrawal_documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->withdrawal_documents->pluck('file_name')->toArray();
        foreach ($request->input('withdrawal_documents', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('withdrawal_documents');
            }
        }

        return redirect()->route('admin.vehicles.index');
    }

    public function show(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle->load('general_state', 'brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client');

        return view('admin.vehicles.show', compact('vehicle'));
    }

    public function destroy(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle->delete();

        return back();
    }

    public function massDestroy(MassDestroyVehicleRequest $request)
    {
        $vehicles = Vehicle::find(request('ids'));

        foreach ($vehicles as $vehicle) {
            $vehicle->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('vehicle_create') && Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Vehicle();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
