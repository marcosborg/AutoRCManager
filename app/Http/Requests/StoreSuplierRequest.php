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
        ];
    }
}
