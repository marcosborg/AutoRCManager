<?php

namespace App\Http\Requests;

use App\Domain\Ownership\OwnershipType;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleOwnershipRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_ownership_create');
    }

    public function rules()
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
            ],
            'owner_type' => [
                'required',
                Rule::in(OwnershipType::options()),
            ],
            'client_id' => [
                'nullable',
                'integer',
                'exists:clients,id',
            ],
            'starts_at' => [
                'required',
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
            ],
            'ends_at' => [
                'nullable',
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
            ],
        ];
    }
}
