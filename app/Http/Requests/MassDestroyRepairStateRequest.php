<?php

namespace App\Http\Requests;

use App\Models\RepairState;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyRepairStateRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('repair_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:repair_states,id',
        ];
    }
}
