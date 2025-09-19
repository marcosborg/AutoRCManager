<?php

namespace App\Http\Requests;

use App\Models\Appreciation;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyAppreciationRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('appreciation_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:appreciations,id',
        ];
    }
}
