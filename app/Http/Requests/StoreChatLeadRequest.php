<?php

namespace App\Http\Requests;

use App\Models\ChatLead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreChatLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(Gate::denies('chat_lead_create') && Gate::denies('chat_lead_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['nullable', 'exists:chat_channels,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'vehicle_title' => ['nullable', 'string', 'max:255'],
            'budget_max' => ['nullable', 'numeric'],
            'wants_financing' => ['nullable', 'boolean'],
            'has_trade_in' => ['nullable', 'boolean'],
            'urgency' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in(array_keys(ChatLead::PRIORITY_SELECT))],
            'status' => ['required', Rule::in(array_keys(ChatLead::STATUS_SELECT))],
            'summary' => ['nullable', 'string'],
            'ai_notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
