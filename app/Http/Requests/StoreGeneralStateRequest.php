<?php

namespace App\Http\Requests;

use App\Models\GeneralState;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreGeneralStateRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('general_state_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'emails' => [
                'string',
                'nullable',
            ],
            'position' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
        ];
    }
}