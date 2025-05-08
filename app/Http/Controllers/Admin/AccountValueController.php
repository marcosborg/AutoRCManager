<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAccountValueRequest;
use App\Http\Requests\StoreAccountValueRequest;
use App\Http\Requests\UpdateAccountValueRequest;
use App\Models\AccountItem;
use App\Models\AccountValue;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AccountValueController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('account_value_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = AccountValue::with(['account_item'])->select(sprintf('%s.*', (new AccountValue)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'account_value_show';
                $editGate      = 'account_value_edit';
                $deleteGate    = 'account_value_delete';
                $crudRoutePart = 'account-values';

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
            $table->addColumn('account_item_name', function ($row) {
                return $row->account_item ? $row->account_item->name : '';
            });

            $table->editColumn('value', function ($row) {
                return $row->value ? $row->value : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'account_item']);

            return $table->make(true);
        }

        $account_items = AccountItem::get();

        return view('admin.accountValues.index', compact('account_items'));
    }

    public function create()
    {
        abort_if(Gate::denies('account_value_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $account_items = AccountItem::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.accountValues.create', compact('account_items'));
    }

    public function store(StoreAccountValueRequest $request)
    {
        $accountValue = AccountValue::create($request->all());

        return redirect()->route('admin.account-values.index');
    }

    public function edit(AccountValue $accountValue)
    {
        abort_if(Gate::denies('account_value_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $account_items = AccountItem::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $accountValue->load('account_item');

        return view('admin.accountValues.edit', compact('accountValue', 'account_items'));
    }

    public function update(UpdateAccountValueRequest $request, AccountValue $accountValue)
    {
        $accountValue->update($request->all());

        return redirect()->route('admin.account-values.index');
    }

    public function show(AccountValue $accountValue)
    {
        abort_if(Gate::denies('account_value_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountValue->load('account_item');

        return view('admin.accountValues.show', compact('accountValue'));
    }

    public function destroy(AccountValue $accountValue)
    {
        abort_if(Gate::denies('account_value_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountValue->delete();

        return back();
    }

    public function massDestroy(MassDestroyAccountValueRequest $request)
    {
        $accountValues = AccountValue::find(request('ids'));

        foreach ($accountValues as $accountValue) {
            $accountValue->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
