<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierOrderItemRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('repair_edit');
    }

    public function rules()
    {
        return [
            'account_category_id' => [
                'required',
                'integer',
                'exists:account_categories,id',
            ],
            'item_name' => [
                'required',
                'string',
                'max:255',
            ],
            'qty_ordered' => [
                'required',
                'numeric',
                'min:0',
            ],
            'qty_received' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }
}
