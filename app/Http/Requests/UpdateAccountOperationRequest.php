<?php

namespace App\Http\Requests;

use App\Models\AccountOperation;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateAccountOperationRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('account_operation_edit');
    }

    public function rules()
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
            ],
            'account_item_id' => [
                'required',
                'integer',
            ],
            'qty' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'total' => [
                'required',
            ],
            'date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
        ];
    }
}
