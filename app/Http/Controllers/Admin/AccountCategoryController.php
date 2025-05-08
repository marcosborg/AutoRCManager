<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAccountCategoryRequest;
use App\Http\Requests\StoreAccountCategoryRequest;
use App\Http\Requests\UpdateAccountCategoryRequest;
use App\Models\AccountCategory;
use App\Models\AccountDepartment;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AccountCategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('account_category_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = AccountCategory::with(['account_department'])->select(sprintf('%s.*', (new AccountCategory)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'account_category_show';
                $editGate      = 'account_category_edit';
                $deleteGate    = 'account_category_delete';
                $crudRoutePart = 'account-categories';

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
            $table->addColumn('account_department_name', function ($row) {
                return $row->account_department ? $row->account_department->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'account_department']);

            return $table->make(true);
        }

        $account_departments = AccountDepartment::get();

        return view('admin.accountCategories.index', compact('account_departments'));
    }

    public function create()
    {
        abort_if(Gate::denies('account_category_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $account_departments = AccountDepartment::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.accountCategories.create', compact('account_departments'));
    }

    public function store(StoreAccountCategoryRequest $request)
    {
        $accountCategory = AccountCategory::create($request->all());

        return redirect()->route('admin.account-categories.index');
    }

    public function edit(AccountCategory $accountCategory)
    {
        abort_if(Gate::denies('account_category_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $account_departments = AccountDepartment::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $accountCategory->load('account_department');

        return view('admin.accountCategories.edit', compact('accountCategory', 'account_departments'));
    }

    public function update(UpdateAccountCategoryRequest $request, AccountCategory $accountCategory)
    {
        $accountCategory->update($request->all());

        return redirect()->route('admin.account-categories.index');
    }

    public function show(AccountCategory $accountCategory)
    {
        abort_if(Gate::denies('account_category_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountCategory->load('account_department');

        return view('admin.accountCategories.show', compact('accountCategory'));
    }

    public function destroy(AccountCategory $accountCategory)
    {
        abort_if(Gate::denies('account_category_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountCategory->delete();

        return back();
    }

    public function massDestroy(MassDestroyAccountCategoryRequest $request)
    {
        $accountCategories = AccountCategory::find(request('ids'));

        foreach ($accountCategories as $accountCategory) {
            $accountCategory->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
