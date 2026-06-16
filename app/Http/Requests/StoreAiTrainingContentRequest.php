<?php

namespace App\Http\Requests;

use App\Models\AiTrainingContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreAiTrainingContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('ai_training_content_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'assistant_id' => ['nullable', 'exists:ai_assistants,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(AiTrainingContent::TYPE_SELECT))],
            'content' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
