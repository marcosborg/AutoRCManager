<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAccountOperationRequest;
use App\Http\Requests\StoreAccountOperationRequest;
use App\Http\Requests\UpdateAccountOperationRequest;
use App\Models\AccountItem;
use App\Models\AccountOperation;
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
            $query = AccountOperation::with(['vehicle', 'account_item'])->select(sprintf('%s.*', (new AccountOperation)->table));
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

            $table->editColumn('account_item.type', function ($row) {
                return $row->account_item ? (is_string($row->account_item) ? $row->account_item : $row->account_item->type) : '';
            });
            $table->editColumn('account_item.total', function ($row) {
                return $row->account_item ? (is_string($row->account_item) ? $row->account_item : $row->account_item->total) : '';
            });
            $table->editColumn('qty', function ($row) {
                return $row->qty ? $row->qty : '';
            });
            $table->editColumn('total', function ($row) {
                return $row->total ? $row->total : '';
            });
            $table->editColumn('balance', function ($row) {
                return $row->balance ? $row->balance : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'vehicle', 'account_item']);

            return $table->make(true);
        }

        $vehicles      = Vehicle::get();
        $account_items = AccountItem::get();

        return view('admin.accountOperations.index', compact('vehicles', 'account_items'));
    }

    public function create()
    {
        abort_if(Gate::denies('account_operation_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $account_items = AccountItem::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.accountOperations.create', compact('account_items', 'vehicles'));
    }

    public function store(StoreAccountOperationRequest $request)
    {
        $accountOperation = AccountOperation::create($request->all());

        return redirect()->route('admin.account-operations.index');
    }

    public function edit(AccountOperation $accountOperation)
    {
        abort_if(Gate::denies('account_operation_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');

        $account_items = AccountItem::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $accountOperation->load('vehicle', 'account_item');

        return view('admin.accountOperations.edit', compact('accountOperation', 'account_items', 'vehicles'));
    }

    public function update(UpdateAccountOperationRequest $request, AccountOperation $accountOperation)
    {
        $accountOperation->update($request->all());

        return redirect()->route('admin.account-operations.index');
    }

    public function show(AccountOperation $accountOperation)
    {
        abort_if(Gate::denies('account_operation_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountOperation->load('vehicle', 'account_item');

        return view('admin.accountOperations.show', compact('accountOperation'));
    }

    public function destroy(AccountOperation $accountOperation)
    {
        abort_if(Gate::denies('account_operation_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountOperation->delete();

        return back();
    }

    public function massDestroy(MassDestroyAccountOperationRequest $request)
    {
        $accountOperations = AccountOperation::find(request('ids'));

        foreach ($accountOperations as $accountOperation) {
            $accountOperation->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}