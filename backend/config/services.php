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

    'payment_gateway' => [
        'webhook_secret' => env('PAYMENT_GATEWAY_WEBHOOK_SECRET'),
    ],

    'spa' => [
        'url' => env('SPA_URL', 'http://localhost:3000'),
    ],

    'whatsapp' => [
        // Selects how SendNotificationJob delivers the "whatsapp" channel —
        // "log" (default, no real send — payload just lands in
        // notification_logs) or "openwa" to send through a self-hosted
        // OpenWA gateway. Set to "openwa" only once OPENWA_* below and a
        // paired WhatsApp session on that gateway are ready.
        'provider' => env('WHATSAPP_PROVIDER', 'log'),
    ],

    'openwa' => [
        'base_url' => env('OPENWA_BASE_URL'),
        'api_key' => env('OPENWA_API_KEY'),
        'session_id' => env('OPENWA_SESSION_ID'),
        'timeout' => env('OPENWA_TIMEOUT', 15),
    ],

];
