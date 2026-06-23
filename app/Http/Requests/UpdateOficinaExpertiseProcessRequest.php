<?php

namespace App\Http\Requests;

use Gate;

class UpdateOficinaExpertiseProcessRequest extends StoreOficinaExpertiseProcessRequest
{
    public function authorize()
    {
        return Gate::allows('oficina_expertise_process_edit') || Gate::allows('oficina_expertise_process_change_status');
    }
}
