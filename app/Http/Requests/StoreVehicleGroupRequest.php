<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVehicleGroupRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_group_create') || Gate::allows('vehicle_lot_create');
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
                'in:lote,unitario',
            ],
            'total_amount' => [
                'nullable',
                'required_if:type,lote',
                'numeric',
                'min:0',
            ],
            'distribution_mode' => [
                'nullable',
                'in:global',
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
            'items.*.adjusted_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items.*.registration_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items.*.tow_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== 'unitario') {
                return;
            }

            foreach ((array) $this->input('vehicles', []) as $vehicleId) {
                $value = $this->input("items.{$vehicleId}.adjusted_price");

                if ($value === null || $value === '') {
                    $validator->errors()->add(
                        "items.{$vehicleId}.adjusted_price",
                        'Indique o preco da viatura no lote discriminado.'
                    );
                }
            }
        });
    }
}
