<?php

namespace App\Http\Requests;

use App\Models\AccountItem;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreAccountItemRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('account_item_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'account_category_id' => [
                'required',
                'integer',
            ],
            'type' => [
                'required',
            ],
        ];
    }
}
