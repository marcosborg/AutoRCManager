<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyClientRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\ClientCharge;
use App\Models\ClientPayment;
use App\Models\Country;
use App\Models\LotPayment;
use App\Models\PaymentMethod;
use App\Models\Provenience;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
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
            $query = Client::with(['country', 'company_country', 'provenience'])->select(sprintf('%s.*', (new Client)->table));
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
            $table->addColumn('provenience_name', function ($row) {
                return $row->provenience ? $row->provenience->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'country', 'company_country', 'provenience']);

            return $table->make(true);
        }

        $countries = Country::get();
        $proveniences = Provenience::where('active', true)->orderBy('name')->get();

        return view('admin.clients.index', compact('countries', 'proveniences'));
    }

    public function create()
    {
        abort_if(Gate::denies('client_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $company_countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $proveniences = Provenience::where('active', true)->orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.clients.create', compact('company_countries', 'countries', 'proveniences'));
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $client->id,
                'name' => $client->name,
            ], Response::HTTP_CREATED);
        }

        return redirect()->route('admin.clients.index');
    }

    public function edit(Client $client)
    {
        abort_if(Gate::denies('client_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $company_countries = Country::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $proveniences = Provenience::where('active', true)->orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $client->load([
            'country',
            'company_country',
            'provenience',
            'vehicles.brand',
            'vehicles.general_state',
            'vehicles.client_payments.payment_method',
            'vehicles.vehicle_groups',
            'payments.payment_method',
            'payments.media',
            'charges',
        ]);

        $currentAccount = $this->buildCurrentAccount($client);
        $paymentMethods = PaymentMethod::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $ledgerEntries = collect();
        $ledgerTotalDebits = 0.0;
        $ledgerTotalCredits = 0.0;
        $ledgerBalance = 0.0;
        $ledgerOutstanding = 0.0;

        return view('admin.clients.edit', compact(
            'client',
            'company_countries',
            'countries',
            'proveniences',
            'currentAccount',
            'paymentMethods',
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

    public function storePayment(Request $request, Client $client)
    {
        abort_if(Gate::denies('client_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'paid_at' => ['required', 'date_format:' . config('panel.date_format')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'max:10240'],
        ]);

        $payment = $client->payments()->create([
            'paid_at' => $data['paid_at'],
            'amount' => $data['amount'],
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->hasFile('proof_file')) {
            $payment->addMediaFromRequest('proof_file')->toMediaCollection('proof_file');
        }

        return redirect()
            ->route('admin.clients.edit', $client->id)
            ->with('message', 'Pagamento registado na conta corrente do cliente.');
    }

    public function showPayment(Client $client, ClientPayment $payment)
    {
        abort_if(Gate::denies('client_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $payment->client_id !== (int) $client->id, Response::HTTP_NOT_FOUND);

        $payment->load('client', 'payment_method', 'media');

        return view('admin.clients.paymentShow', compact('client', 'payment'));
    }

    public function storeCharge(Request $request, Client $client)
    {
        abort_if(Gate::denies('client_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'charged_at' => ['required', 'date_format:' . config('panel.date_format')],
            'description' => ['required', 'string', 'max:255'],
            'charge_amount' => ['required', 'numeric', 'min:0.01'],
            'charge_notes' => ['nullable', 'string'],
        ]);

        $client->charges()->create([
            'charged_at' => $data['charged_at'],
            'description' => $data['description'],
            'amount' => $data['charge_amount'],
            'notes' => $data['charge_notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.clients.edit', $client->id)
            ->with('message', 'Debito registado na conta corrente do cliente.');
    }

    public function show(Client $client)
    {
        abort_if(Gate::denies('client_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $client->load('country', 'company_country', 'provenience');

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

    private function buildCurrentAccount(Client $client): array
    {
        $lots = VehicleGroup::with([
            'items',
            'vehicles',
            'payments.payment_method',
        ])
            ->where(function ($query) use ($client) {
                $query->where('customer_id', $client->id)
                    ->orWhereHas('clients', function ($clientsQuery) use ($client) {
                        $clientsQuery->where('clients.id', $client->id);
                    });
            })
            ->get()
            ->unique('id')
            ->values();

        $clientLotIds = $lots->pluck('id');

        $vehicleRows = $client->vehicles
            ->sortBy(function (Vehicle $vehicle) {
                return $vehicle->sale_date ?: $vehicle->license ?: $vehicle->id;
            })
            ->map(function (Vehicle $vehicle) use ($clientLotIds) {
                $vehicleLots = $vehicle->vehicle_groups
                    ->whereIn('id', $clientLotIds)
                    ->values();

                $debit = $this->calculateVehicleSalesTotal($vehicle);
                $credit = (float) $vehicle->client_payments->sum('amount');
                $countsInTotals = $vehicleLots->isEmpty();

                return [
                    'vehicle' => $vehicle,
                    'lots' => $vehicleLots,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $debit - $credit,
                    'counts_in_totals' => $countsInTotals,
                ];
            })
            ->values();

        $lotRows = $lots
            ->sortBy(function (VehicleGroup $lot) {
                return $lot->name ?: $lot->id;
            })
            ->map(function (VehicleGroup $lot) {
                $debit = (float) $lot->effective_total;
                $credit = (float) $lot->payments
                    ->where('approval_status', LotPayment::STATUS_APPROVED)
                    ->sum('amount');

                return [
                    'lot' => $lot,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $debit - $credit,
                    'vehicles_count' => $lot->vehicles->count(),
                ];
            })
            ->values();

        $vehicleDebit = (float) $vehicleRows
            ->where('counts_in_totals', true)
            ->sum('debit');
        $vehicleCredit = (float) $vehicleRows
            ->where('counts_in_totals', true)
            ->sum('credit');
        $clientDirectCredit = (float) $client->payments->sum('amount');
        $clientChargeDebit = (float) $client->charges->sum('amount');
        $lotDebit = (float) $lotRows->sum('debit');
        $lotCredit = (float) $lotRows->sum('credit');
        $debit = $vehicleDebit + $lotDebit + $clientChargeDebit;
        $credit = $vehicleCredit + $lotCredit + $clientDirectCredit;

        return [
            'vehicleRows' => $vehicleRows,
            'lotRows' => $lotRows,
            'chargeRows' => $this->buildChargeRows($client),
            'receiptRows' => $this->buildReceiptRows($client, $lots, $clientLotIds),
            'totals' => [
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $debit - $credit,
                'vehicle_debit' => $vehicleDebit,
                'vehicle_credit' => $vehicleCredit,
                'lot_debit' => $lotDebit,
                'lot_credit' => $lotCredit,
                'client_direct_credit' => $clientDirectCredit,
                'client_charge_debit' => $clientChargeDebit,
            ],
        ];
    }

    private function buildChargeRows(Client $client)
    {
        return $client->charges
            ->sortByDesc(function (ClientCharge $charge) {
                return $charge->getRawOriginal('charged_at') ?: '';
            })
            ->map(function (ClientCharge $charge) {
                return [
                    'charge' => $charge,
                    'charged_at' => $charge->charged_at,
                    'description' => $charge->description,
                    'amount' => (float) $charge->amount,
                    'notes' => $charge->notes,
                ];
            })
            ->values();
    }

    private function buildReceiptRows(Client $client, $lots, $clientLotIds)
    {
        $directPayments = $client->payments->map(function ($payment) {
            return [
                'paid_at' => $payment->getRawOriginal('paid_at'),
                'date' => $payment->paid_at,
                'source' => 'Conta corrente',
                'reference' => 'Pagamento geral do cliente',
                'payment_method' => $payment->payment_method->name ?? '',
                'amount' => (float) $payment->amount,
                'status' => 'Conta no saldo',
                'counts_in_balance' => true,
                'notes' => $payment->notes,
                'url' => route('admin.clients.payments.show', [$payment->client_id, $payment->id]),
            ];
        });

        $vehiclePayments = $client->vehicles->flatMap(function (Vehicle $vehicle) use ($clientLotIds) {
            $belongsToClientLot = $vehicle->vehicle_groups
                ->whereIn('id', $clientLotIds)
                ->isNotEmpty();

            return $vehicle->client_payments->map(function ($payment) use ($vehicle) {
                return [
                    'paid_at' => $payment->getRawOriginal('paid_at'),
                    'date' => $payment->paid_at,
                    'source' => 'Viatura',
                    'reference' => trim(($vehicle->license ?: $vehicle->foreign_license ?: 'Sem matricula') . ' - ' . ($vehicle->brand->name ?? '') . ' - ' . ($vehicle->model ?? '')),
                    'payment_method' => $payment->payment_method->name ?? '',
                    'amount' => (float) $payment->amount,
                    'status' => 'Conta no saldo',
                    'counts_in_balance' => true,
                    'notes' => null,
                    'url' => route('admin.vehicles.edit', $vehicle->id),
                ];
            })->map(function (array $row) use ($belongsToClientLot) {
                if (! $belongsToClientLot) {
                    return $row;
                }

                $row['status'] = 'Valor no lote';
                $row['counts_in_balance'] = false;

                return $row;
            });
        });

        $lotPayments = $lots->flatMap(function (VehicleGroup $lot) {
            return $lot->payments->map(function (LotPayment $payment) use ($lot) {
                $countsInBalance = $payment->approval_status === LotPayment::STATUS_APPROVED;

                return [
                    'paid_at' => $payment->getRawOriginal('paid_at'),
                    'date' => $payment->paid_at,
                    'source' => 'Lote',
                    'reference' => $lot->name,
                    'payment_method' => $payment->payment_method->name ?? '',
                    'amount' => (float) $payment->amount,
                    'status' => $countsInBalance ? 'Aprovado' : ucfirst((string) $payment->approval_status),
                    'counts_in_balance' => $countsInBalance,
                    'notes' => $payment->notes,
                    'url' => route('admin.vehicle-groups.show', $lot->id),
                ];
            });
        });

        return $directPayments
            ->concat($vehiclePayments)
            ->concat($lotPayments)
            ->sortByDesc(function ($row) {
                return $row['paid_at'] ?: '';
            })
            ->values();
    }

    private function calculateVehicleSalesTotal(Vehicle $vehicle): float
    {
        return (float) ($vehicle->pvp ?? 0)
            + (float) ($vehicle->sales_iuc ?? 0)
            + (float) ($vehicle->sales_tow ?? 0)
            + (float) ($vehicle->sales_transfer ?? 0)
            + (float) ($vehicle->sales_others ?? 0);
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
