<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierOrderRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('repair_create');
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
            'invoice_total_confirmed' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'parts_total_confirmed' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'invoice_attachment' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ];
    }
}
