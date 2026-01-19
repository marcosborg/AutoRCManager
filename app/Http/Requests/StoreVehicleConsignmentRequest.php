<?php

namespace App\Http\Requests;

use App\Domain\Consignments\ConsignmentStatus;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleConsignmentRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_consignment_create');
    }

    public function rules()
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
            ],
            'from_unit_id' => [
                'required',
                'integer',
                'exists:operational_units,id',
            ],
            'to_unit_id' => [
                'required',
                'integer',
                'exists:operational_units,id',
            ],
            'reference_value' => [
                'required',
                'numeric',
            ],
            'starts_at' => [
                'required',
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
            ],
            'status' => [
                'nullable',
                Rule::in(ConsignmentStatus::options()),
            ],
        ];
    }
}
