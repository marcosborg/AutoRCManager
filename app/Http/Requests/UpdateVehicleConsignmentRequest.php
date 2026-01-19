<?php

namespace App\Http\Requests;

use App\Domain\Consignments\ConsignmentStatus;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleConsignmentRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_consignment_edit');
    }

    public function rules()
    {
        return [
            'ends_at' => [
                'required',
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
            ],
            'status' => [
                'required',
                Rule::in(ConsignmentStatus::options()),
            ],
        ];
    }
}
