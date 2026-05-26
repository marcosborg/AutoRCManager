<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartOrderRequest extends StorePartOrderRequest
{
    public function authorize()
    {
        return Gate::allows('part_order_edit');
    }
}
