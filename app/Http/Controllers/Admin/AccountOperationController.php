<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Finance\AccountDepartments;
use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAccountOperationRequest;
use App\Http\Requests\StoreAccountOperationRequest;
use App\Http\Requests\UpdateAccountOperationRequest;
use App\Models\AccountItem;
use App\Models\AccountOperation;
use App\Models\PaymentMethod;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AccountOperationController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('account_operation_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = AccountOperation::with(['vehicle', 'account_item', 'payment_method'])
                ->select(sprintf('%s.*', (new AccountOperation)->table));

            if (! $this->canViewFinancialSensitive()) {
                $query->whereDoesntHave('account_item.account_category', function ($query) {
                    $query->where('account_department_id', AccountDepartments::ACQUISITION);
                });
            }
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'account_operation_show';
                $editGate      = 'account_operation_edit';
                $deleteGate    = 'account_operation_delete';
                $crudRoutePart = 'account-operations';

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
            $table->addColumn('vehicle_license', function ($row) {
                return $row->vehicle ? $row->vehicle->license : '';
            });

            $table->addColumn('account_item_name', function ($row) {
                return $row->account_item ? $row->account_item->name : '';
            });

            $table->editColumn('qrt', function ($row) {
                return $row->qrt ? $row->qrt : '';
            });
            $table->editColumn('total', function ($row) {
                return $row->total ? $row->total : '';
            });
            $table->addColumn('payment_method_name', function ($row) {
                return $row->payment_method ? $row->payment_method->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'vehicle', 'account_item', 'payment_method']);

            return $table->make(true);
        }

        $vehicles = Vehicle::get();
        $accountItemsQuery = AccountItem::query();

        if (! $this->canViewFinancialSensitive()) {
            $accountItemsQuery->whereHas('account_category', function ($query) {
                $query->where('account_department_id', '!=', AccountDepartments::ACQUISITION);
            });
        }

        $account_items = $accountItemsQuery->get();
        $payment_methods = PaymentMethod::get();

        return view('admin.accountOperations.index', compact('vehicles', 'account_items', 'payment_methods'));
    }

    public function create()
    {
        abort_if(Gate::denies('account_operation_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $accountItemsQuery = AccountItem::query();

        if (! $this->canViewFinancialSensitive()) {
            $accountItemsQuery->whereHas('account_category', function ($query) {
                $query->where('account_department_id', '!=', AccountDepartments::ACQUISITION);
            });
        }

        $account_items = $accountItemsQuery->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_methods = PaymentMethod::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.accountOperations.create', compact('account_items', 'payment_methods', 'vehicles'));
    }

    public function store(StoreAccountOperationRequest $request)
    {
        $accountItem = AccountItem::with('account_category')->find($request->input('account_item_id'));

        if ($accountItem && $this->isAcquisitionItem($accountItem)) {
            $this->abortIfSensitiveDenied();
        }

        $accountOperation = AccountOperation::create($request->all());

        return redirect()->route('admin.account-operations.index');
    }

    public function edit(AccountOperation $accountOperation)
    {
        abort_if(Gate::denies('account_operation_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($this->isAcquisitionOperation($accountOperation)) {
            $this->abortIfSensitiveDenied();
        }

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $accountItemsQuery = AccountItem::query();

        if (! $this->canViewFinancialSensitive()) {
            $accountItemsQuery->whereHas('account_category', function ($query) {
                $query->where('account_department_id', '!=', AccountDepartments::ACQUISITION);
            });
        }

        $account_items = $accountItemsQuery->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_methods = PaymentMethod::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $accountOperation->load('vehicle', 'account_item', 'payment_method');

        return view('admin.accountOperations.edit', compact('accountOperation', 'account_items', 'payment_methods', 'vehicles'));
    }

    public function update(UpdateAccountOperationRequest $request, AccountOperation $accountOperation)
    {
        if ($this->isAcquisitionOperation($accountOperation)) {
            $this->abortIfSensitiveDenied();
        }

        $accountItem = AccountItem::with('account_category')->find($request->input('account_item_id'));

        if ($accountItem && $this->isAcquisitionItem($accountItem)) {
            $this->abortIfSensitiveDenied();
        }

        $accountOperation->update($request->all());

        return redirect()->route('admin.account-operations.index');
    }

    public function show(AccountOperation $accountOperation)
    {
        abort_if(Gate::denies('account_operation_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($this->isAcquisitionOperation($accountOperation)) {
            $this->abortIfSensitiveDenied();
        }

        $accountOperation->load('vehicle', 'account_item', 'payment_method');

        return view('admin.accountOperations.show', compact('accountOperation'));
    }

    public function destroy(AccountOperation $accountOperation)
    {
        abort_if(Gate::denies('account_operation_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($this->isAcquisitionOperation($accountOperation)) {
            $this->abortIfSensitiveDenied();
        }

        $accountOperation->delete();

        return back();
    }

    public function massDestroy(MassDestroyAccountOperationRequest $request)
    {
        $accountOperations = AccountOperation::find(request('ids'));

        if (! $this->canViewFinancialSensitive()) {
            $hasAcquisition = $accountOperations
                ->filter(fn($operation) => $this->isAcquisitionOperation($operation))
                ->isNotEmpty();

            if ($hasAcquisition) {
                $this->abortIfSensitiveDenied();
            }
        }

        foreach ($accountOperations as $accountOperation) {
            $accountOperation->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeAjax(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'account_item_id' => 'required|exists:account_items,id',
            'total' => 'required|numeric|min:0',
        ]);

        $accountItem = AccountItem::with('account_category')->find($validated['account_item_id']);

        if ($accountItem && $this->isAcquisitionItem($accountItem)) {
            $this->abortIfSensitiveDenied();
        }

        $operation = AccountOperation::create([
            'vehicle_id' => $validated['vehicle_id'],
            'account_item_id' => $validated['account_item_id'],
            'qty' => 1,
            'total' => $validated['total'],
            'date' => now()->format('Y-m-d'), // ← Aqui corrigido
        ]);

        return response()->json([
            'message' => 'Operação gravada com sucesso.',
            'item_name' => $operation->account_item->name,
            'total' => $operation->total,
        ]);
    }

    public function delete(AccountOperation $operation)
    {
        if ($this->isAcquisitionOperation($operation)) {
            $this->abortIfSensitiveDenied();
        }

        $operation->delete();

        return response()->json(['success' => true]);
    }

    private function canViewFinancialSensitive(): bool
    {
        return Gate::allows('financial_sensitive_access');
    }

    private function abortIfSensitiveDenied(): void
    {
        abort_if(Gate::denies('financial_sensitive_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function isAcquisitionOperation(AccountOperation $operation): bool
    {
        $operation->loadMissing('account_item.account_category');

        return (int) optional(optional($operation->account_item)->account_category)->account_department_id === AccountDepartments::ACQUISITION;
    }

    private function isAcquisitionItem(AccountItem $accountItem): bool
    {
        $accountItem->loadMissing('account_category');

        return (int) optional($accountItem->account_category)->account_department_id === AccountDepartments::ACQUISITION;
    }
}
