<?php

namespace App\Http\Requests;

use App\Models\Suplier;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreSuplierRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('suplier_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string'],
            'nif' => ['nullable', 'string', 'max:191'],
            'average_delivery_days' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
