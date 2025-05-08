<?php

namespace App\Http\Requests;

use App\Models\AccountDepartment;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreAccountDepartmentRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('account_department_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
        ];
    }
}
