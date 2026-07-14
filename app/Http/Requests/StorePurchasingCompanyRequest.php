<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchasingCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('purchasing_company_manage');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchasing_companies', 'name'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indique o nome da empresa compradora.',
            'name.unique' => 'Esta empresa compradora já existe.',
        ];
    }
}
