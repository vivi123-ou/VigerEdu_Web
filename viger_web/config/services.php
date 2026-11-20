<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Kết nối CRM (HOP_DONG_KET_NOI mục 4): form liên hệ gửi POST {url}/leads với token kênh website.
    // Để trống CRM_API_URL / CRM_API_TOKEN => không gửi, form chỉ lưu ở web chính.
    'crm' => [
        'url' => env('CRM_API_URL'),
        'token' => env('CRM_API_TOKEN'),
        'consent_version' => env('CRM_CONSENT_VERSION', 'lien-he-2026-11'),
    ],

];
