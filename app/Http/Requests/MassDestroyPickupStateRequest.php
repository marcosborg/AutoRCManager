<?php

namespace App\Http\Requests;

use App\Models\PickupState;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyPickupStateRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('pickup_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:pickup_states,id',
        ];
    }
}
