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
            $query = Vehicle::with(['brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client'])->select(sprintf('%s.*', (new Vehicle)->table));
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

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->editColumn('license', function ($row) {
                return $row->license ? $row->license : '';
            });
            $table->addColumn('brand_name', function ($row) {
                return $row->brand ? $row->brand->name : '';
            });

            $table->editColumn('model', function ($row) {
                return $row->model ? $row->model : '';
            });
            $table->editColumn('version', function ($row) {
                return $row->version ? $row->version : '';
            });
            $table->editColumn('year', function ($row) {
                return $row->year ? $row->year : '';
            });
            $table->editColumn('vehicle_identification_number_vin', function ($row) {
                return $row->vehicle_identification_number_vin ? $row->vehicle_identification_number_vin : '';
            });

            $table->editColumn('color', function ($row) {
                return $row->color ? $row->color : '';
            });
            $table->editColumn('fuel', function ($row) {
                return $row->fuel ? $row->fuel : '';
            });
            $table->editColumn('kilometers', function ($row) {
                return $row->kilometers ? $row->kilometers : '';
            });
            $table->editColumn('inspec_b', function ($row) {
                return $row->inspec_b ? $row->inspec_b : '';
            });
            $table->addColumn('seller_client_name', function ($row) {
                return $row->seller_client ? $row->seller_client->name : '';
            });

            $table->addColumn('buyer_client_name', function ($row) {
                return $row->buyer_client ? $row->buyer_client->name : '';
            });

            $table->editColumn('purchase_and_sale_agreement', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->purchase_and_sale_agreement ? 'checked' : null) . '>';
            });
            $table->editColumn('copy_of_the_citizen_card', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->copy_of_the_citizen_card ? 'checked' : null) . '>';
            });
            $table->editColumn('tax_identification_card', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->tax_identification_card ? 'checked' : null) . '>';
            });
            $table->editColumn('copy_of_the_stamp_duty_receipt', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->copy_of_the_stamp_duty_receipt ? 'checked' : null) . '>';
            });
            $table->editColumn('vehicle_registration_document', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->vehicle_registration_document ? 'checked' : null) . '>';
            });
            $table->editColumn('vehicle_ownership_title', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->vehicle_ownership_title ? 'checked' : null) . '>';
            });
            $table->editColumn('vehicle_keys', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->vehicle_keys ? 'checked' : null) . '>';
            });
            $table->editColumn('vehicle_manuals', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->vehicle_manuals ? 'checked' : null) . '>';
            });
            $table->editColumn('release_of_reservation_or_mortgage', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->release_of_reservation_or_mortgage ? 'checked' : null) . '>';
            });
            $table->editColumn('leasing_agreement', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->leasing_agreement ? 'checked' : null) . '>';
            });
            $table->editColumn('cables', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->cables ? 'checked' : null) . '>';
            });

            $table->editColumn('documents', function ($row) {
                if (! $row->documents) {
                    return '';
                }
                $links = [];
                foreach ($row->documents as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank">' . trans('global.downloadFile') . '</a>';
                }

                return implode(', ', $links);
            });
            $table->editColumn('purchase_price', function ($row) {
                return $row->purchase_price ? $row->purchase_price : '';
            });
            $table->editColumn('photos', function ($row) {
                if (! $row->photos) {
                    return '';
                }
                $links = [];
                foreach ($row->photos as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank"><img src="' . $media->getUrl('thumb') . '" width="50px" height="50px"></a>';
                }

                return implode(' ', $links);
            });
            $table->addColumn('suplier_name', function ($row) {
                return $row->suplier ? $row->suplier->name : '';
            });

            $table->editColumn('invoice', function ($row) {
                if (! $row->invoice) {
                    return '';
                }
                $links = [];
                foreach ($row->invoice as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank">' . trans('global.downloadFile') . '</a>';
                }

                return implode(', ', $links);
            });
            $table->editColumn('inicial', function ($row) {
                if (! $row->inicial) {
                    return '';
                }
                $links = [];
                foreach ($row->inicial as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank"><img src="' . $media->getUrl('thumb') . '" width="50px" height="50px"></a>';
                }

                return implode(' ', $links);
            });
            $table->addColumn('payment_status_name', function ($row) {
                return $row->payment_status ? $row->payment_status->name : '';
            });

            $table->editColumn('amount_paid', function ($row) {
                return $row->amount_paid ? $row->amount_paid : '';
            });
            $table->addColumn('carrier_name', function ($row) {
                return $row->carrier ? $row->carrier->name : '';
            });

            $table->editColumn('storage_location', function ($row) {
                return $row->storage_location ? $row->storage_location : '';
            });
            $table->editColumn('withdrawal_authorization', function ($row) {
                return $row->withdrawal_authorization ? $row->withdrawal_authorization : '';
            });
            $table->editColumn('withdrawal_authorization_file', function ($row) {
                if (! $row->withdrawal_authorization_file) {
                    return '';
                }
                $links = [];
                foreach ($row->withdrawal_authorization_file as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank">' . trans('global.downloadFile') . '</a>';
                }

                return implode(', ', $links);
            });

            $table->editColumn('withdrawal_documents', function ($row) {
                if (! $row->withdrawal_documents) {
                    return '';
                }
                $links = [];
                foreach ($row->withdrawal_documents as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank">' . trans('global.downloadFile') . '</a>';
                }

                return implode(', ', $links);
            });
            $table->addColumn('pickup_state_name', function ($row) {
                return $row->pickup_state ? $row->pickup_state->name : '';
            });

            $table->editColumn('total_price', function ($row) {
                return $row->total_price ? $row->total_price : '';
            });
            $table->editColumn('minimum_price', function ($row) {
                return $row->minimum_price ? $row->minimum_price : '';
            });
            $table->editColumn('pvp', function ($row) {
                return $row->pvp ? $row->pvp : '';
            });
            $table->addColumn('client_name', function ($row) {
                return $row->client ? $row->client->name : '';
            });

            $table->editColumn('client_amount_paid', function ($row) {
                return $row->client_amount_paid ? $row->client_amount_paid : '';
            });
            $table->editColumn('client_registration', function ($row) {
                return $row->client_registration ? $row->client_registration : '';
            });
            $table->editColumn('chekin_documents', function ($row) {
                return $row->chekin_documents ? $row->chekin_documents : '';
            });

            $table->editColumn('first_key', function ($row) {
                return $row->first_key ? $row->first_key : '';
            });
            $table->editColumn('scuts', function ($row) {
                return $row->scuts ? $row->scuts : '';
            });
            $table->editColumn('key', function ($row) {
                return $row->key ? $row->key : '';
            });
            $table->editColumn('manuals', function ($row) {
                return $row->manuals ? $row->manuals : '';
            });
            $table->editColumn('elements_with_vehicle', function ($row) {
                return $row->elements_with_vehicle ? $row->elements_with_vehicle : '';
            });
            $table->editColumn('sale_notes', function ($row) {
                return $row->sale_notes ? $row->sale_notes : '';
            });
            $table->editColumn('local', function ($row) {
                return $row->local ? $row->local : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'brand', 'seller_client', 'buyer_client', 'purchase_and_sale_agreement', 'copy_of_the_citizen_card', 'tax_identification_card', 'copy_of_the_stamp_duty_receipt', 'vehicle_registration_document', 'vehicle_ownership_title', 'vehicle_keys', 'vehicle_manuals', 'release_of_reservation_or_mortgage', 'leasing_agreement', 'cables', 'documents', 'photos', 'suplier', 'invoice', 'inicial', 'payment_status', 'carrier', 'withdrawal_authorization_file', 'withdrawal_documents', 'pickup_state', 'client']);

            return $table->make(true);
        }

        $brands           = Brand::get();
        $clients          = Client::get();
        $supliers         = Suplier::get();
        $payment_statuses = PaymentStatus::get();
        $carriers         = Carrier::get();
        $pickup_states    = PickupState::get();

        return view('admin.vehicles.index', compact('brands', 'clients', 'supliers', 'payment_statuses', 'carriers', 'pickup_states'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seller_clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $buyer_clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_statuses = PaymentStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carriers = Carrier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $pickup_states = PickupState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicles.create', compact('brands', 'buyer_clients', 'carriers', 'clients', 'payment_statuses', 'pickup_states', 'seller_clients', 'supliers'));
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

        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seller_clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $buyer_clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_statuses = PaymentStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carriers = Carrier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $pickup_states = PickupState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle->load('brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client');

        return view('admin.vehicles.edit', compact('brands', 'buyer_clients', 'carriers', 'clients', 'payment_statuses', 'pickup_states', 'seller_clients', 'supliers', 'vehicle'));
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

        $vehicle->load('brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client');

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