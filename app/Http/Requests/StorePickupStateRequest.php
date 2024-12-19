<?php

namespace App\Http\Requests;

use App\Models\PickupState;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StorePickupStateRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('pickup_state_create');
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
