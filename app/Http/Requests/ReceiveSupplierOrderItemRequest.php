<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveSupplierOrderItemRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('repair_edit');
    }

    public function rules()
    {
        return [
            'qty_received' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ];
    }
}
