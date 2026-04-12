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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'playwright' => [
        'node_path' => env('PLAYWRIGHT_NODE_PATH'),
        'browsers_path' => env('PLAYWRIGHT_BROWSERS_PATH'),
        'library_path' => env('PLAYWRIGHT_LIBRARY_PATH', storage_path('app/playwright-libs/lib64')),
        'timeout_ms' => env('PLAYWRIGHT_TIMEOUT_MS', 45000),
        'viewport_width' => env('PLAYWRIGHT_VIEWPORT_WIDTH', 1440),
        'viewport_height' => env('PLAYWRIGHT_VIEWPORT_HEIGHT', 960),
    ],

];
