<?php

namespace App\Http\Requests;

use Gate;

class UpdatePartReceiptRequest extends StorePartReceiptRequest
{
    public function authorize()
    {
        return Gate::allows('part_receipt_edit');
    }
}
