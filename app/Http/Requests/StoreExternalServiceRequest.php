<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreExternalServiceRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('external_service_create');
    }

    public function rules()
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'suplier_id' => ['nullable', 'integer', 'exists:supliers,id'],
            'requested_by_id' => ['nullable', 'integer', 'exists:users,id'],
            'description' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,normal,urgent'],
            'status' => ['required', 'in:requested,scheduled,in_progress,completed,cancelled'],
            'requested_delivery_days' => ['nullable', 'integer', 'min:0'],
            'expected_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'invoice_file' => ['nullable', 'file', 'max:10240'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
