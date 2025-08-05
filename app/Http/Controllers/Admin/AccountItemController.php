<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAccountItemRequest;
use App\Http\Requests\StoreAccountItemRequest;
use App\Http\Requests\UpdateAccountItemRequest;
use App\Models\AccountCategory;
use App\Models\AccountItem;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AccountItemController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('account_item_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = AccountItem::with(['account_category'])->select(sprintf('%s.*', (new AccountItem)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'account_item_show';
                $editGate      = 'account_item_edit';
                $deleteGate    = 'account_item_delete';
                $crudRoutePart = 'account-items';

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
            $table->addColumn('account_category_name', function ($row) {
                return $row->account_category ? $row->account_category->name : '';
            });

            $table->editColumn('type', function ($row) {
                return $row->type ? AccountItem::TYPE_RADIO[$row->type] : '';
            });
            $table->editColumn('total', function ($row) {
                return $row->total ? $row->total : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'account_category']);

            return $table->make(true);
        }

        $account_categories = AccountCategory::get();

        return view('admin.accountItems.index', compact('account_categories'));
    }

    public function create()
    {
        abort_if(Gate::denies('account_item_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $account_categories = AccountCategory::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.accountItems.create', compact('account_categories'));
    }

    public function store(StoreAccountItemRequest $request)
    {
        $accountItem = AccountItem::create($request->all());

        return redirect()->route('admin.account-items.index');
    }

    public function edit(AccountItem $accountItem)
    {
        abort_if(Gate::denies('account_item_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $account_categories = AccountCategory::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $accountItem->load('account_category');

        return view('admin.accountItems.edit', compact('accountItem', 'account_categories'));
    }

    public function update(UpdateAccountItemRequest $request, AccountItem $accountItem)
    {
        $accountItem->update($request->all());

        return redirect()->route('admin.account-items.index');
    }

    public function show(AccountItem $accountItem)
    {
        abort_if(Gate::denies('account_item_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountItem->load('account_category');

        return view('admin.accountItems.show', compact('accountItem'));
    }

    public function destroy(AccountItem $accountItem)
    {
        abort_if(Gate::denies('account_item_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountItem->delete();

        return back();
    }

    public function massDestroy(MassDestroyAccountItemRequest $request)
    {
        $accountItems = AccountItem::find(request('ids'));

        foreach ($accountItems as $accountItem) {
            $accountItem->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function getByCategory($id)
    {
        $items = AccountItem::where('account_category_id', $id)
            ->select('id', 'name', 'total')
            ->get();

        return response()->json($items);
    }
}
