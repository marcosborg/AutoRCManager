<?php

namespace App\Http\Requests;

use Gate;

class UpdatePartPaymentRequest extends StorePartPaymentRequest
{
    public function authorize()
    {
        return Gate::allows('part_payment_edit');
    }
}
