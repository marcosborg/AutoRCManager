<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierOrderRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('repair_edit');
    }

    public function rules()
    {
        return [
            'suplier_id' => [
                'required',
                'integer',
                'exists:supliers,id',
            ],
            'repair_id' => [
                'nullable',
                'integer',
                'exists:repairs,id',
            ],
            'order_date' => [
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
