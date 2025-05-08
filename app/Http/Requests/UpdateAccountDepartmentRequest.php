<?php

namespace App\Http\Requests;

use App\Models\AccountDepartment;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateAccountDepartmentRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('account_department_edit');
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
