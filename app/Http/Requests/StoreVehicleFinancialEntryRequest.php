<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleFinancialEntryRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_financial_entry_create');
    }

    public function rules()
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
            ],
            'entry_type' => [
                'required',
                'in:cost,revenue',
            ],
            'category' => [
                'required',
                'string',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],
            'entry_date' => [
                'required',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
