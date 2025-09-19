<?php

namespace App\Http\Requests;

use App\Models\Appreciation;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreAppreciationRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('appreciation_create');
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
