<?php

namespace App\Http\Requests;

use App\Models\Timelog;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreTimelogRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('timelog_create');
    }

    public function rules()
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
            ],
            'user_id' => [
                'required',
                'integer',
            ],
            'start_time' => [
                'required',
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
            ],
            'end_time' => [
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
                'nullable',
            ],
        ];
    }
}
