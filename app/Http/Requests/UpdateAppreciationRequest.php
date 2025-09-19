<?php

namespace App\Http\Requests;

use App\Models\Appreciation;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateAppreciationRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('appreciation_edit');
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
