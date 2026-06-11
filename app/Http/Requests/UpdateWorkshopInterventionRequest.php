<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateWorkshopInterventionRequest extends StoreWorkshopInterventionRequest
{
    public function authorize(): bool
    {
        return Gate::allows('workshop_planning_edit');
    }
}
