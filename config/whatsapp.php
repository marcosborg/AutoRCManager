<?php

return [
    'transport' => env('WHATSAPP_TRANSPORT', 'email'),
    'legacy_polling_enabled' => env('WHATSAPP_LEGACY_POLLING_ENABLED', false),
    'lead_notifications_enabled' => env('WHATSAPP_LEAD_NOTIFICATIONS_ENABLED', false),
    'graph_version' => env('WHATSAPP_GRAPH_VERSION', env('META_GRAPH_VERSION', 'v25.0')),
    'waba_id' => env('WHATSAPP_WABA_ID'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'app_secret' => env('WHATSAPP_APP_SECRET', env('META_APP_SECRET')),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    'templates' => [
        'customer_greeting' => [
            'name' => env('WHATSAPP_TEMPLATE_CUSTOMER_GREETING', 'autorc_nova_lead_cliente_v1'),
            'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'pt_PT'),
        ],
        'seller_lead' => [
            'name' => env('WHATSAPP_TEMPLATE_SELLER_LEAD', 'autorc_nova_lead_vendedor_v1'),
            'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'pt_PT'),
        ],
    ],
];
