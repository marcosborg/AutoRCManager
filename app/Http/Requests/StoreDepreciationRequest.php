<?php

namespace App\Http\Requests;

use App\Models\Depreciation;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreDepreciationRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('depreciation_create');
    }

    public function rules()
    {
        return [
            'license_plate' => [
                'string',
                'required',
            ],
            'value' => [
                'required',
            ],
        ];
    }
}
