<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StorePartOrderRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('part_order_create');
    }

    public function rules()
    {
        return [
            'repair_id' => ['nullable', 'integer', 'exists:repairs,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'requested_by_id' => ['nullable', 'integer', 'exists:users,id'],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'suplier_id' => ['nullable', 'integer', 'exists:supliers,id'],
            'priority' => ['required', 'in:low,normal,urgent'],
            'status' => ['required', 'in:draft,requesting_quotes,ordered,partially_received,received,delayed,cancelled'],
            'requested_delivery_days' => ['nullable', 'integer', 'min:0'],
            'expected_delivery_date' => ['nullable', 'date'],
            'actual_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.reference' => ['nullable', 'string', 'max:191'],
            'items.*.description' => ['nullable', 'string', 'max:191'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.iva_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.status' => ['nullable', 'in:pending,ordered,shipped,received,installed,returned'],
            'items.*.observations' => ['nullable', 'string'],
        ];
    }
}
