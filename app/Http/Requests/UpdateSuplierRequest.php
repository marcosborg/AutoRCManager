<?php

namespace App\Http\Requests;

use App\Models\Suplier;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateSuplierRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('suplier_edit');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
        ];
    }
}
