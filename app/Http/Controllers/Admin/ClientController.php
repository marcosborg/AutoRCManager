<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyClientRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Country;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('client_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Client::with(['country', 'company_country'])->select(sprintf('%s.*', (new Client)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'client_show';
                $editGate = 'client_edit';
                $deleteGate = 'client_delete';
                $crudRoutePart = 'clients';

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
            $table->editColumn('vat', function ($row) {
                return $row->vat ? $row->vat : '';
            });
            $table->editColumn('address', function ($row) {
                return $row->address ? $row->address : '';
            });
            $table->editColumn('location', function ($row) {
                return $row->location ? $row->location : '';
            });
            $table->editColumn('zip', function ($row) {
                return $row->zip ? $row->zip : '';
            });
            $table->editColumn('phone', function ($row) {
                return $row->phone ? $row->phone : '';
            });
            $table->editColumn('email', function ($row) {
                return $row->email ? $row->email : '';
            });
            $table->addColumn('country_name', function ($row) {
                return $row->country ? $row->country->name : '';
            });

            $table->editColumn('company_name', function ($row) {
                return $row->company_name ? $row->company_name : '';
            });
            $table->editColumn('company_vat', function ($row) {
                return $row->company_vat ? $row->company_vat : '';
            });
            $table->editColumn('company_address', function ($row) {
                return $row->company_address ? $row->company_address : '';
            });
            $table->editColumn('company_location', function ($row) {
                return $row->company_location ? $row->company_location : '';
            });
            $table->editColumn('company_zip', function ($row) {
                return $row->company_zip ? $row->company_zip : '';
            });
            $table->editColumn('company_phone', function ($row) {
                return $row->company_phone ? $row->company_phone : '';
            });
            $table->editColumn('company_email', function ($row) {
                return $row->company_email ? $row->company_email : '';
            });
            $table->addColumn('company_country_name', function ($row) {
                return $row->company_country ? $row->company_country->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'country', 'company_country']);

            return $table->make(true);
        }

        $countries = Country::get();

        return view('admin.clients.index', compact('countries'));
    }

    public function create()
    {
        abort_if(Gate::denies('client_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $company_countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.clients.create', compact('company_countries', 'countries'));
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->all());

        return redirect()->route('admin.clients.index');
    }

    public function edit(Client $client)
    {
        abort_if(Gate::denies('client_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $company_countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $client->load('country', 'company_country', 'vehicles');

        $ledgerEntries = collect();
        $ledgerTotalDebits = 0.0;
        $ledgerTotalCredits = 0.0;
        $ledgerBalance = 0.0;
        $ledgerOutstanding = 0.0;

        return view('admin.clients.edit', compact(
            'client',
            'company_countries',
            'countries',
            'ledgerEntries',
            'ledgerTotalDebits',
            'ledgerTotalCredits',
            'ledgerBalance',
            'ledgerOutstanding'
        ));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->all());

        return redirect()->route('admin.clients.index');
    }

    public function show(Client $client)
    {
        abort_if(Gate::denies('client_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $client->load('country', 'company_country');

        $ledgerEntries = collect();
        $ledgerTotalDebits = 0.0;
        $ledgerTotalCredits = 0.0;
        $ledgerBalance = 0.0;
        $ledgerOutstanding = 0.0;

        return view('admin.clients.show', compact(
            'client',
            'ledgerEntries',
            'ledgerTotalDebits',
            'ledgerTotalCredits',
            'ledgerBalance',
            'ledgerOutstanding'
        ));
    }

    public function reconciliation(Client $client)
    {
        abort(Response::HTTP_GONE, 'Modulo financeiro descontinuado.');
    }

    public function destroy(Client $client)
    {
        abort_if(Gate::denies('client_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $client->delete();

        return back();
    }

    public function massDestroy(MassDestroyClientRequest $request)
    {
        $clients = Client::find(request('ids'));

        foreach ($clients as $client) {
            $client->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
