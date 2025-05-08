<?php

namespace App\Http\Requests;

use App\Models\AccountValue;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateAccountValueRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('account_value_edit');
    }

    public function rules()
    {
        return [
            'account_item_id' => [
                'required',
                'integer',
            ],
            'value' => [
                'required',
            ],
        ];
    }
}
