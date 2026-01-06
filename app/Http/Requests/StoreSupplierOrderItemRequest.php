<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierOrderItemRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('repair_edit');
    }

    public function rules()
    {
        return [
            'supplier_order_id' => [
                'required',
                'integer',
                'exists:supplier_orders,id',
            ],
            'account_category_id' => [
                'required',
                'integer',
                'exists:account_categories,id',
            ],
            'item_name' => [
                'required',
                'string',
            ],
            'qty_ordered' => [
                'required',
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
