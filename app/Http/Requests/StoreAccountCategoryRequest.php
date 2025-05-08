<?php

namespace App\Http\Requests;

use App\Models\AccountCategory;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreAccountCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('account_category_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'account_department_id' => [
                'required',
                'integer',
            ],
        ];
    }
}
