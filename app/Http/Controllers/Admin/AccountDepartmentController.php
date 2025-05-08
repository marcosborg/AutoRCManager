<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAccountDepartmentRequest;
use App\Http\Requests\StoreAccountDepartmentRequest;
use App\Http\Requests\UpdateAccountDepartmentRequest;
use App\Models\AccountDepartment;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AccountDepartmentController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('account_department_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = AccountDepartment::query()->select(sprintf('%s.*', (new AccountDepartment)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'account_department_show';
                $editGate      = 'account_department_edit';
                $deleteGate    = 'account_department_delete';
                $crudRoutePart = 'account-departments';

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

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        return view('admin.accountDepartments.index');
    }

    public function create()
    {
        abort_if(Gate::denies('account_department_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.accountDepartments.create');
    }

    public function store(StoreAccountDepartmentRequest $request)
    {
        $accountDepartment = AccountDepartment::create($request->all());

        return redirect()->route('admin.account-departments.index');
    }

    public function edit(AccountDepartment $accountDepartment)
    {
        abort_if(Gate::denies('account_department_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.accountDepartments.edit', compact('accountDepartment'));
    }

    public function update(UpdateAccountDepartmentRequest $request, AccountDepartment $accountDepartment)
    {
        $accountDepartment->update($request->all());

        return redirect()->route('admin.account-departments.index');
    }

    public function show(AccountDepartment $accountDepartment)
    {
        abort_if(Gate::denies('account_department_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.accountDepartments.show', compact('accountDepartment'));
    }

    public function destroy(AccountDepartment $accountDepartment)
    {
        abort_if(Gate::denies('account_department_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $accountDepartment->delete();

        return back();
    }

    public function massDestroy(MassDestroyAccountDepartmentRequest $request)
    {
        $accountDepartments = AccountDepartment::find(request('ids'));

        foreach ($accountDepartments as $accountDepartment) {
            $accountDepartment->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
