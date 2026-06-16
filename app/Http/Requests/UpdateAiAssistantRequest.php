<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdateAiAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('ai_assistant_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        $assistant = $this->route('ai_assistant') ?: $this->route('aiAssistant');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('ai_assistants', 'slug')->ignore($assistant)],
            'company_name' => ['nullable', 'string', 'max:255'],
            'commercial_phone' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'system_prompt' => ['nullable', 'string'],
            'rules' => ['nullable', 'string'],
            'forbidden_topics' => ['nullable', 'string'],
            'allowed_topics' => ['nullable', 'string'],
            'escalation_rules' => ['nullable', 'string'],
            'default_language' => ['nullable', 'string', 'max:20'],
        ];
    }
}
