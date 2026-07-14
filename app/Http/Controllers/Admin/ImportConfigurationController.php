<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchasingCompanyRequest;
use App\Models\OperationalAlertRecipient;
use App\Models\PurchasingCompany;
use App\Models\User;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ImportConfigurationController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('import_configuration_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = PurchasingCompany::orderBy('name')->get();
        $users = User::orderBy('name')->pluck('name', 'id');
        $tollsRecipient = OperationalAlertRecipient::firstOrCreate([
            'key' => OperationalAlertRecipient::KEY_TOLLS,
        ]);

        return view('admin.importConfiguration.index', compact('companies', 'tollsRecipient', 'users'));
    }

    public function storeCompany(StorePurchasingCompanyRequest $request)
    {
        $company = PurchasingCompany::create([
            'name' => trim($request->input('name')),
            'active' => true,
            'created_by_id' => $request->user()?->id,
        ]);

        return response()->json([
            'id' => $company->id,
            'name' => $company->name,
        ], Response::HTTP_CREATED);
    }

    public function updateCompany(Request $request, PurchasingCompany $company)
    {
        abort_if(Gate::denies('purchasing_company_manage'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('purchasing_companies', 'name')->ignore($company)],
            'active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($company, $data, $request): void {
            $previousName = $company->name;
            $company->update([
                'name' => trim($data['name']),
                'active' => $request->boolean('active'),
            ]);

            if ($previousName !== $company->name) {
                Vehicle::where('our_registration', $previousName)->update([
                    'our_registration' => $company->name,
                ]);
            }
        });

        return redirect()->route('admin.import-configuration.index')->with('message', 'Empresa compradora atualizada.');
    }

    public function updateTollsRecipient(Request $request)
    {
        abort_if(Gate::denies('import_configuration_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        OperationalAlertRecipient::updateOrCreate(
            ['key' => OperationalAlertRecipient::KEY_TOLLS],
            ['user_id' => $data['user_id']]
        );

        return redirect()->route('admin.import-configuration.index')->with('message', 'Responsável por Portagens atualizada.');
    }
}
