<?php

namespace App\Http\Requests;

use App\Models\VehicleGroup;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreVehicleGroupRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_group_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'vehicles' => [
                'array',
            ],
            'vehicles.*' => [
                'integer',
                'exists:vehicles,id',
            ],
            'clients' => [
                'array',
            ],
            'clients.*' => [
                'integer',
                'exists:clients,id',
            ],
        ];
    }
}
