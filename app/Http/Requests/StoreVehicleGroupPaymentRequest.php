<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleGroupPaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('return_to')) {
            $this->merge(['return_to' => 'show']);
        }
    }

    public function authorize(): bool
    {
        return Gate::allows('vehicle_lot_payment_create') || Gate::allows('vehicle_group_edit');
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'in:show,edit'],
            'payment_type' => ['required', 'in:money,trade_in'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'paid_at' => ['required', 'date_format:'.config('panel.date_format')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'invoiced_amount' => ['nullable', 'numeric', 'min:0'],
            'bank_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_2_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'max:10240'],
            'trade_in_license' => ['nullable', 'string', 'max:50'],
            'trade_in_brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'trade_in_model' => ['nullable', 'string', 'max:255'],
            'trade_in_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'trade_in_kilometers' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
