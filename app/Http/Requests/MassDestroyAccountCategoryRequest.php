<?php

namespace App\Http\Requests;

use App\Models\AccountCategory;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyAccountCategoryRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('account_category_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:account_categories,id',
        ];
    }
}
