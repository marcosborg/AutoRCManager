<?php

namespace App\Http\Requests;

use App\Models\AccountOperation;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyAccountOperationRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('account_operation_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:account_operations,id',
        ];
    }
}
