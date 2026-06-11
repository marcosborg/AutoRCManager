<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\WorkshopIntervention;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreWorkshopInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('workshop_planning_create');
    }

    public function rules(): array
    {
        return [
            'repair_id' => ['required', 'integer', 'exists:repairs,id'],
            'type_id' => ['required', 'integer', 'exists:workshop_intervention_types,id'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'planned_start_date' => ['required', 'date'],
            'planned_end_date' => ['required', 'date', 'after_or_equal:planned_start_date'],
            'status' => ['required', Rule::in(array_keys(WorkshopIntervention::STATUS_SELECT))],
            'mechanic_ids' => ['required', 'array', 'min:1'],
            'mechanic_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = collect($this->input('mechanic_ids', []))->map(fn ($id) => (int) $id)->unique();
            if ($ids->isEmpty()) {
                return;
            }

            $mechanicCount = User::query()
                ->whereIn('id', $ids)
                ->whereHas('roles', fn ($query) => $query->whereIn('title', ['Mecânico', 'Mecanico']))
                ->count();

            if ($mechanicCount !== $ids->count()) {
                $validator->errors()->add('mechanic_ids', 'Só podem ser atribuídos utilizadores com o perfil Mecânico.');
            }
        });
    }
}
