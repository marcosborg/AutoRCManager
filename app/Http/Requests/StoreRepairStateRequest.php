<?php

namespace App\Http\Requests;

use App\Models\RepairState;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreRepairStateRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('repair_state_create');
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
