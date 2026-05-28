<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialInstitution;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class FinancialInstitutionController extends Controller
{
    public function store(Request $request)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_institutions', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $financialInstitution = FinancialInstitution::create([
            'name' => $data['name'],
            'active' => true,
        ]);

        return response()->json([
            'id' => $financialInstitution->id,
            'name' => $financialInstitution->name,
        ], Response::HTTP_CREATED);
    }
}
