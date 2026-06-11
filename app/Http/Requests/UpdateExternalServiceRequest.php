<?php

namespace App\Http\Requests;

use Gate;

class UpdateExternalServiceRequest extends StoreExternalServiceRequest
{
    public function authorize()
    {
        return Gate::allows('external_service_edit');
    }
}
