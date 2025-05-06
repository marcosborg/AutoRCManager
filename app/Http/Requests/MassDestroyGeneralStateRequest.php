<?php

namespace App\Http\Requests;

use App\Models\GeneralState;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyGeneralStateRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('general_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:general_states,id',
        ];
    }
}