<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('workshop_cash_transfer');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_cash_box_id' => ['required', 'integer', 'exists:cash_boxes,id', 'different:to_cash_box_id'],
            'to_cash_box_id' => ['required', 'integer', 'exists:cash_boxes,id'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'occurred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'proofs' => ['nullable', 'array'],
            'proofs.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480'],
        ];
    }
}
