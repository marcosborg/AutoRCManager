<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\GeneralState;
use App\Models\PaymentStatus;
use App\Models\PickupState;
use App\Models\PurchasingCompany;
use App\Models\Suplier;
use App\Models\Vehicle;
use App\Support\LicensePlate;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class SalesController extends Controller
{
    use CsvImportTrait, MediaUploadingTrait;

    public function index(Request $request, $general_state_id = null)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Vehicle::with([
                'general_state',
                'brand',
                'suplier',
                'payment_status',
                'carrier',
                'pickup_state',
                'client',
                'source_trade_in',
                'media',
            ])->select(sprintf('%s.*', (new Vehicle)->table));

            // pega do path (/{general_state_id?}) ou da query (?general_state_id=)
            $gsId = $general_state_id ?? $request->input('general_state_id') ?? $request->route('general_state_id');

            if (! empty($gsId)) {
                $query->where('general_state_id', $gsId);
            }

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'vehicle_show';
                $editGate = 'vehicle_edit';
                $deleteGate = 'vehicle_delete';
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
            $table->filterColumn('license', function ($query, $keyword) {
                LicensePlate::applySearch($query, (string) $keyword, ['license']);
            });
            $table->editColumn('foreign_license', function ($row) {
                return $row->foreign_license ? $row->foreign_license : '';
            });
            $table->filterColumn('foreign_license', function ($query, $keyword) {
                LicensePlate::applySearch($query, (string) $keyword, ['foreign_license']);
            });
            $table->editColumn('our_registration', function ($row) {
                return $row->our_registration ? $row->our_registration : '';
            });
            $table->addColumn('vehicle_thumb', function ($row) {
                return $this->vehicleThumbnailHtml($row);
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
            $table->editColumn('is_invoiced', function ($row) {
                return $row->is_invoiced ? 'Sim' : 'Nao';
            });
            $table->filterColumn('is_invoiced', function ($query, $keyword) {
                $value = mb_strtolower(trim((string) $keyword, " \t\n\r\0\x0B^$"));
                $truthy = ['sim', '1', 'true', 'yes', 'y'];
                $falsy = ['nao', 'nÃ£o', 'não', '0', 'false', 'no', 'n'];

                if (in_array($value, $truthy, true)) {
                    $query->where('is_invoiced', true);

                    return;
                }

                if (in_array($value, $falsy, true)) {
                    $query->where('is_invoiced', false);

                    return;
                }
            });
            $table->editColumn('source_trade_in', function ($row) {
                return $row->source_trade_in ? 'Sim' : 'Nao';
            });
            $table->filterColumn('source_trade_in', function ($query, $keyword) {
                $value = mb_strtolower(trim((string) $keyword, " \t\n\r\0\x0B^$"));
                $truthy = ['sim', '1', 'true', 'yes', 'y'];
                $falsy = ['nao', 'nÃƒÂ£o', 'nÃ£o', '0', 'false', 'no', 'n'];

                if (in_array($value, $truthy, true)) {
                    $query->whereHas('source_trade_in');

                    return;
                }

                if (in_array($value, $falsy, true)) {
                    $query->whereDoesntHave('source_trade_in');

                    return;
                }
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

            $table->rawColumns(['actions', 'placeholder', 'general_state', 'brand', 'suplier', 'client', 'vehicle_thumb']);

            return $table->make(true);
        }

        $general_states = GeneralState::get();
        $brands = Brand::get();
        $supliers = Suplier::get();
        $payment_statuses = PaymentStatus::get();
        $carriers = Carrier::get();
        $pickup_states = PickupState::get();
        $clients = Client::get();
        $purchasingCompanies = PurchasingCompany::where('active', true)->orderBy('name')->get();

        return view('admin.sales.index', compact('general_states', 'brands', 'supliers', 'payment_statuses', 'carriers', 'pickup_states', 'clients', 'purchasingCompanies'));
    }

    private function vehicleThumbnailHtml(Vehicle $vehicle): string
    {
        $media = $vehicle->getFirstMedia('photos') ?: $vehicle->getFirstMedia('inicial');
        $placeholder = '<span class="vehicle-list-thumb vehicle-list-thumb-placeholder" style="display:none"><i class="fa fa-car"></i></span>';

        if ($media) {
            $url = e($this->productionMediaUrl($media->getUrl('thumb') ?: $media->getUrl()));
            $alt = e($vehicle->license ?: $vehicle->model ?: 'Viatura');

            return '<img src="'.$url.'" alt="'.$alt.'" class="vehicle-list-thumb" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';">'.$placeholder;
        }

        return '<span class="vehicle-list-thumb vehicle-list-thumb-placeholder"><i class="fa fa-car"></i></span>';
    }

    private function productionMediaUrl(string $url): string
    {
        $mediaBaseUrl = rtrim(env('AUTORC_MEDIA_BASE_URL', 'https://autorcmanager.pt'), '/');
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY);

        if (! $host || in_array($host, ['127.0.0.1', 'localhost', '0.0.0.0'], true)) {
            return $mediaBaseUrl.$path.($query ? '?'.$query : '');
        }

        return $url;
    }
}
