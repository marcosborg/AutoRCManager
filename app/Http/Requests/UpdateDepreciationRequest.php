<?php

namespace App\Http\Requests;

use App\Models\Depreciation;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateDepreciationRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('depreciation_edit');
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
