<?php

namespace App\Http\Requests;

use App\Models\VehicleGroup;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateVehicleGroupRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_group_edit') || Gate::allows('vehicle_lot_edit');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'customer_id' => [
                'nullable',
                'integer',
                'exists:clients,id',
            ],
            'type' => [
                'required',
                'in:unitario,lote',
            ],
            'total_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'distribution_mode' => [
                'required',
                'in:proportional,equal',
            ],
            'notes' => [
                'nullable',
                'string',
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
            'wholesale_pvp' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items' => [
                'array',
            ],
            'items.*.original_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items.*.adjusted_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }
}
