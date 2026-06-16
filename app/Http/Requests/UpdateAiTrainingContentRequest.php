<?php

namespace App\Http\Requests;

class UpdateAiTrainingContentRequest extends StoreAiTrainingContentRequest
{
    public function authorize(): bool
    {
        abort_if(\Illuminate\Support\Facades\Gate::denies('ai_training_content_edit'), \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }
}
