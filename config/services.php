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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ilovepdf' => [
        'public_key' => env('ILOVEPDF_PUBLIC_KEY', 'project_public_41dc72571a0bd6ad0d9c1229730cd855_ZIGHDa44f17a64d5e15831bb2e2a11dd008b0'),
        'secret_key' => env('ILOVEPDF_SECRET_KEY', 'secret_key_ba0f4b29e9567962833debc10e68a7b8_yf51Ub09aa52f5dd0ced36f669ca8ab3ff75b'),
    ],

];
