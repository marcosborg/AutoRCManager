<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StorePartPaymentRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('part_payment_create');
    }

    public function rules()
    {
        return [
            'part_order_id' => ['required', 'integer', 'exists:part_orders,id'],
            'suplier_id' => ['nullable', 'integer', 'exists:supliers,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,credit_card,mbway,current_account,other'],
            'payment_condition' => ['nullable', 'in:immediate,30_days,60_days,90_days'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:191'],
            'paid_by_id' => ['nullable', 'integer', 'exists:users,id'],
            'payment_status' => ['required', 'in:pending,partially_paid,paid,overdue,cancelled'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
