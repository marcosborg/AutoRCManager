<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StorePartReceiptRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('part_receipt_create');
    }

    public function rules()
    {
        return [
            'part_order_id' => ['required', 'integer', 'exists:part_orders,id'],
            'received_at' => ['nullable', 'date'],
            'received_location' => ['nullable', 'string', 'max:191'],
            'received_by_id' => ['nullable', 'integer', 'exists:users,id'],
            'signature_name' => ['nullable', 'string', 'max:191'],
            'observations' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
