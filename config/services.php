<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'page_id' => env('META_PAGE_ID'),
        'form_id' => env('META_FORM_ID'),
        'access_token' => env('META_PAGE_ACCESS_TOKEN'),
        'graph_version' => env('META_GRAPH_VERSION', 'v25.0'),
        'inbound_token' => env('META_INBOUND_WEBHOOK_TOKEN'),
        'lead_reconciliation' => [
            // Keep this disabled until a System User token with leads access is configured.
            'enabled' => env('META_LEAD_RECONCILIATION_ENABLED', false),
            'form_ids' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('META_LEAD_RECONCILIATION_FORM_IDS', env('META_FORM_ID', '')))
            ))),
            'lookback_minutes' => (int) env('META_LEAD_RECONCILIATION_LOOKBACK_MINUTES', 1440),
            'page_size' => (int) env('META_LEAD_RECONCILIATION_PAGE_SIZE', 100),
            'max_pages_per_form' => (int) env('META_LEAD_RECONCILIATION_MAX_PAGES', 10),
        ],
    ],

];
