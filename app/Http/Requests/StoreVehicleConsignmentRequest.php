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
                'nullable',
                'required_without:to_unit_name',
                'integer',
                'exists:operational_units,id',
            ],
            'to_unit_name' => [
                'nullable',
                'required_without:to_unit_id',
                'string',
                'max:255',
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'to_unit_id' => $this->input('to_unit_id') ?: null,
            'to_unit_name' => trim((string) $this->input('to_unit_name')) ?: null,
        ]);
    }
}
