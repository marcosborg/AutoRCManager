<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyClientRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\AccountOperation;
use App\Models\Client;
use App\Models\Country;
use App\Models\Timelog;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Vehicle;

class ClientController extends Controller
{
    use CsvImportTrait;

    private const DEPARTMENT_PURCHASE = 1;
    private const DEPARTMENT_GARAGE = 2;
    private const DEPARTMENT_SALE = 3;

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

        $client->load('country', 'company_country', 'vehicles', 'ledger_entries');

        $ledgerEntries = $client->ledger_entries->sortByDesc('entry_date')->values();
        $ledgerTotalDebits = (float) $ledgerEntries->where('entry_type', 'debit')->sum('amount');
        $ledgerTotalCredits = (float) $ledgerEntries->where('entry_type', 'credit')->sum('amount');
        $ledgerBalance = $ledgerTotalCredits - $ledgerTotalDebits;
        $ledgerOutstanding = max($ledgerTotalDebits - $ledgerTotalCredits, 0);

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

        $client->load('country', 'company_country', 'ledger_entries');

        $ledgerEntries = $client->ledger_entries->sortByDesc('entry_date')->values();
        $ledgerTotalDebits = (float) $ledgerEntries->where('entry_type', 'debit')->sum('amount');
        $ledgerTotalCredits = (float) $ledgerEntries->where('entry_type', 'credit')->sum('amount');
        $ledgerBalance = $ledgerTotalCredits - $ledgerTotalDebits;
        $ledgerOutstanding = max($ledgerTotalDebits - $ledgerTotalCredits, 0);

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
        abort_if(Gate::denies('client_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $canViewSensitive = $this->canViewFinancialSensitive();
        $clientViewMode = request()->boolean('client_view') || ! $canViewSensitive;

        $client->load([
            'vehicles.brand',
            'vehicles.general_state',
            'vehicles.vehicle_groups',
            'vehicle_groups.vehicles.brand',
            'vehicle_groups.vehicles.general_state',
        ]);

        $directVehicles = $client->vehicles;
        $groupVehicles = $client->vehicle_groups->flatMap->vehicles;

        $vehicles = $directVehicles->concat($groupVehicles)->unique('id')->values();
        $vehicles->load('vehicle_groups');
        $vehicleIds = $vehicles->pluck('id')->filter();

        if ($vehicleIds->isEmpty()) {
            $operations = collect();
        } else {
            $operationsQuery = AccountOperation::with(['account_item.account_category', 'vehicle'])
                ->whereIn('vehicle_id', $vehicleIds);

            if ($clientViewMode) {
                $operationsQuery->whereHas('account_item.account_category', function ($query) {
                    $query->where('account_department_id', self::DEPARTMENT_SALE);
                });
            }

            $operations = $operationsQuery->get();
        }

        $operationsByDepartment = $this->splitOperationsByDepartment($operations, $clientViewMode);

        $timelogs = $clientViewMode || $vehicleIds->isEmpty()
            ? collect()
            : Timelog::with(['user', 'vehicle'])
                ->whereIn('vehicle_id', $vehicleIds)
                ->whereNotNull('rounded_minutes')
                ->orderBy('start_time')
                ->get();

        $hourPrice = 25;

        $financial = $this->calculateFinancialSummaryForVehicles($vehicles, $operationsByDepartment, $timelogs, $hourPrice, $clientViewMode);
        $vehicleBreakdown = $this->buildVehicleBreakdown($vehicles, $operations, $timelogs, $hourPrice, $clientViewMode);
        $groupBreakdown = $this->buildGroupBreakdown($client->vehicle_groups, $vehicles, $operations, $timelogs, $hourPrice, $clientViewMode);

        return view('admin.clients.reconciliation', compact(
            'client',
            'financial',
            'operationsByDepartment',
            'vehicleBreakdown',
            'groupBreakdown',
            'hourPrice',
            'clientViewMode',
            'canViewSensitive'
        ));
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

    private function splitOperationsByDepartment(Collection $operations, bool $clientViewMode = false): array
    {
        if ($clientViewMode) {
            return [
                'aquisition' => collect(),
                'garage' => collect(),
                'sale' => $this->filterOperationsByDepartment($operations, self::DEPARTMENT_SALE),
            ];
        }

        return [
            'aquisition' => $this->filterOperationsByDepartment($operations, self::DEPARTMENT_PURCHASE),
            'garage' => $this->filterOperationsByDepartment($operations, self::DEPARTMENT_GARAGE),
            'sale' => $this->filterOperationsByDepartment($operations, self::DEPARTMENT_SALE),
        ];
    }

    private function filterOperationsByDepartment(Collection $operations, int $departmentId): Collection
    {
        return $operations->filter(function ($operation) use ($departmentId) {
            $category = optional(optional($operation->account_item)->account_category);

            return $category && (int) $category->account_department_id === $departmentId;
        })->values();
    }

    private function calculateFinancialSummaryForVehicles(Collection $vehicles, array $operationsByDepartment, Collection $timelogs, int $hourPrice, bool $clientViewMode = false): array
    {
        $finalSalesTarget = (float) $vehicles->sum(function ($vehicle) {
            return (float) ($vehicle->pvp ?? 0)
                + (float) ($vehicle->sales_iuc ?? 0)
                + (float) ($vehicle->sales_tow ?? 0)
                + (float) ($vehicle->sales_transfer ?? 0)
                + (float) ($vehicle->sales_others ?? 0);
        });

        $saleTotal = (float) ($operationsByDepartment['sale']->sum('total') ?? 0);
        $saleBalance = $finalSalesTarget - $saleTotal;

        if ($clientViewMode) {
            return [
                'purchasePrice' => 0,
                'purchaseTotal' => 0,
                'purchaseBalance' => 0,
                'garageTotal' => 0,
                'finalSalesTarget' => $finalSalesTarget,
                'saleTotal' => $saleTotal,
                'saleBalance' => $saleBalance,
                'totalMinutes' => 0,
                'labourCost' => 0,
                'invested' => 0,
                'profit' => 0,
                'roi' => 0,
                'theoreticalProfit' => 0,
            ];
        }

        $commissionTotal = (float) $vehicles->sum(fn($vehicle) => $vehicle->commission ?? 0);
        $purchasePrice = (float) $vehicles->sum(fn($vehicle) => $vehicle->purchase_price ?? 0) + $commissionTotal;
        $purchaseTotal = (float) ($operationsByDepartment['aquisition']->sum('total') ?? 0) + $commissionTotal;
        $purchaseBalance = $purchasePrice - $purchaseTotal;

        $garageTotal = (float) ($operationsByDepartment['garage']->sum('total') ?? 0);

        $totalMinutes = (int) $timelogs->sum('rounded_minutes');
        $labourCost = ($totalMinutes / 60) * $hourPrice;

        $invested = $purchaseTotal + $garageTotal + $labourCost;
        $profit = $saleTotal - $invested;
        $roi = $invested > 0 ? ($profit / $invested) * 100 : 0;
        $theoreticalProfit = $finalSalesTarget - $invested;

        return [
            'purchasePrice' => $purchasePrice,
            'purchaseTotal' => $purchaseTotal,
            'purchaseBalance' => $purchaseBalance,
            'garageTotal' => $garageTotal,
            'finalSalesTarget' => $finalSalesTarget,
            'saleTotal' => $saleTotal,
            'saleBalance' => $saleBalance,
            'totalMinutes' => $totalMinutes,
            'labourCost' => $labourCost,
            'invested' => $invested,
            'profit' => $profit,
            'roi' => $roi,
            'theoreticalProfit' => $theoreticalProfit,
        ];
    }

    private function buildVehicleBreakdown(Collection $vehicles, Collection $operations, Collection $timelogs, int $hourPrice, bool $clientViewMode = false): Collection
    {
        $operationsByVehicle = $operations->groupBy('vehicle_id');
        $timelogsByVehicle = $timelogs->groupBy('vehicle_id');

        return $vehicles->map(function ($vehicle) use ($operationsByVehicle, $timelogsByVehicle, $hourPrice) {
            $ops = $operationsByVehicle->get($vehicle->id, collect());

            $purchaseOps = $this->filterOperationsByDepartment($ops, self::DEPARTMENT_PURCHASE);
            $garageOps = $this->filterOperationsByDepartment($ops, self::DEPARTMENT_GARAGE);
            $saleOps = $this->filterOperationsByDepartment($ops, self::DEPARTMENT_SALE);

            $saleTarget = (float) ($vehicle->pvp ?? 0)
                + (float) ($vehicle->sales_iuc ?? 0)
                + (float) ($vehicle->sales_tow ?? 0)
                + (float) ($vehicle->sales_transfer ?? 0)
                + (float) ($vehicle->sales_others ?? 0);

            $vehicleTimelogs = $timelogsByVehicle->get($vehicle->id, collect());
            $minutes = (int) $vehicleTimelogs->sum('rounded_minutes');
            $labourCost = ($minutes / 60) * $hourPrice;

            $saleTotal = (float) $saleOps->sum('total');
            $saleBalance = $saleTarget - $saleTotal;

            if ($clientViewMode) {
                return [
                    'vehicle' => $vehicle,
                    'purchase_price' => 0,
                    'purchase_total' => 0,
                    'purchase_balance' => 0,
                    'garage_total' => 0,
                    'sale_target' => $saleTarget,
                    'sale_total' => $saleTotal,
                    'sale_balance' => $saleBalance,
                    'minutes' => 0,
                    'labour_cost' => 0,
                    'invested' => 0,
                    'profit' => 0,
                    'groups' => $vehicle->vehicle_groups->pluck('name')->filter()->values(),
                ];
            }

            $commission = (float) ($vehicle->commission ?? 0);
            $invested = (float) $purchaseOps->sum('total') + $commission + (float) $garageOps->sum('total') + $labourCost;

            return [
                'vehicle' => $vehicle,
                'purchase_price' => (float) ($vehicle->purchase_price ?? 0) + $commission,
                'purchase_total' => (float) $purchaseOps->sum('total') + $commission,
                'purchase_balance' => ((float) ($vehicle->purchase_price ?? 0) + $commission) - ((float) $purchaseOps->sum('total') + $commission),
                'garage_total' => (float) $garageOps->sum('total'),
                'sale_target' => $saleTarget,
                'sale_total' => $saleTotal,
                'sale_balance' => $saleBalance,
                'minutes' => $minutes,
                'labour_cost' => $labourCost,
                'invested' => $invested,
                'profit' => $saleTotal - $invested,
                'groups' => $vehicle->vehicle_groups->pluck('name')->filter()->values(),
            ];
        })
            ->sortBy(fn($row) => $row['vehicle']->license ?? $row['vehicle']->id)
            ->values();
    }

    private function buildGroupBreakdown(Collection $vehicleGroups, Collection $vehicles, Collection $operations, Collection $timelogs, int $hourPrice, bool $clientViewMode = false): Collection
    {
        $allowedIds = $vehicles->pluck('id')->filter()->unique();
        $operationsByVehicle = $operations->groupBy('vehicle_id');
        $timelogsByVehicle = $timelogs->groupBy('vehicle_id');

        return $vehicleGroups->map(function ($group) use ($allowedIds, $operationsByVehicle, $timelogsByVehicle, $hourPrice) {
            $vehicles = $group->vehicles->filter(fn($vehicle) => $allowedIds->contains($vehicle->id))->values();
            $vehicleIds = $vehicles->pluck('id')->filter();

            $ops = $vehicleIds->isEmpty()
                ? collect()
                : $vehicleIds->flatMap(fn($id) => $operationsByVehicle->get($id, collect()));

            $operationsByDepartment = $this->splitOperationsByDepartment($ops, $clientViewMode);

            $timelogs = $vehicleIds->isEmpty()
                ? collect()
                : $vehicleIds->flatMap(fn($id) => $timelogsByVehicle->get($id, collect()));

            $financial = $this->calculateFinancialSummaryForVehicles($vehicles, $operationsByDepartment, $timelogs, $hourPrice, $clientViewMode);

            return [
                'group' => $group,
                'vehicles_count' => $vehicles->count(),
                'financial' => $financial,
            ];
        });
    }

    private function canViewFinancialSensitive(): bool
    {
        return Gate::allows('financial_sensitive_access');
    }
}
