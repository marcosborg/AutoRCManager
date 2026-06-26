<?php

return [
    'default_assistant_slug' => env('AI_DEFAULT_ASSISTANT_SLUG', 'carsete'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_api_key' => env('OPENAI_API_KEY'),
    'node_api_token' => env('NODE_API_TOKEN'),
    'max_context_messages' => (int) env('AI_MAX_CONTEXT_MESSAGES', 20),
    'commercial_phone' => env('AI_COMMERCIAL_PHONE', '912273402'),
    'company_name' => env('AI_COMPANY_NAME', 'Car 7'),
    'chat_standby' => filter_var(env('AI_CHAT_STANDBY', false), FILTER_VALIDATE_BOOL),
    'auto_reply_enabled' => filter_var(env('AI_AUTO_REPLY_ENABLED', true), FILTER_VALIDATE_BOOL),
    'human_takeover_idle_release_minutes' => (int) env('AI_HUMAN_TAKEOVER_IDLE_RELEASE_MINUTES', 5),
    'lead_context_reset_minutes' => (int) env('AI_LEAD_CONTEXT_RESET_MINUTES', 60),
    'lead_delivery_channel' => env('AI_LEAD_DELIVERY_CHANNEL', 'whatsapp'),
    'lead_email_cc_addresses' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AI_LEAD_EMAIL_CC_ADDRESSES', ''))
    ))),
    'lead_whatsapp_cc_phones' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AI_LEAD_WHATSAPP_CC_PHONES', '912239578'))
    ))),
];
