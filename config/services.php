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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'psgc' => [
        'base_url' => env('PSGC_BASE_URL', 'https://psgc.cloud/api/v2'),
        'timeout' => (int) env('PSGC_TIMEOUT', 10),
        'retry_times' => (int) env('PSGC_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('PSGC_RETRY_SLEEP_MS', 250),
        'cache_ttl_minutes' => (int) env('PSGC_CACHE_TTL_MINUTES', 1440),
    ],

];
