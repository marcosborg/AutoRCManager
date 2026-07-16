<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopCashExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('workshop_cash_expense');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cash_category_id' => ['required', 'integer', 'exists:cash_categories,id'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'proofs' => ['required', 'array', 'min:1'],
            'proofs.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480'],
        ];
    }
}
