<?php

return [
    'default_assistant_slug' => env('AI_DEFAULT_ASSISTANT_SLUG', 'carsete'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_api_key' => env('OPENAI_API_KEY'),
    'node_api_token' => env('NODE_API_TOKEN'),
    'max_context_messages' => (int) env('AI_MAX_CONTEXT_MESSAGES', 20),
    'commercial_phone' => env('AI_COMMERCIAL_PHONE', '913203600'),
    'company_name' => env('AI_COMPANY_NAME', 'Car 7'),
    'auto_reply_enabled' => filter_var(env('AI_AUTO_REPLY_ENABLED', true), FILTER_VALIDATE_BOOL),
    'human_takeover_idle_release_minutes' => (int) env('AI_HUMAN_TAKEOVER_IDLE_RELEASE_MINUTES', 5),
];
