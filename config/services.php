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

    'cloudflare' => [
        'api_url' => env('CLOUDFLARE_API_URL', 'https://api.cloudflare.com/client/v4'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'api_token_edit_zone_dns' => env('CLOUDFLARE_API_TOKEN_EDIT_ZONE_DNS'),
        'wrangler_path' => env('WRANGLER_PATH', '/usr/local/bin/wrangler'),
    ],

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN', 'TOKEN'),
    ],

    'godaddy' => [
        'api_key'    => env('GODADDY_API_KEY'),
        'api_secret' => env('GODADDY_API_SECRET'),
        'api_url'    => env('GODADDY_API_URL'),
        'shopper_id'    => env('GODADDY_SHOPPER_ID'),
    ],

    'godaddy_tuan' => [
        'api_key'    => env('GODADDY_TUAN_API_KEY'),
        'api_secret' => env('GODADDY_TUAN_API_SECRET'),
        'shopper_id'    => env('GODADDY_TUAN_SHOPPER_ID'),
    ],

    'godaddy_linh' => [
        'api_key'    => env('GODADDY_LINH_API_KEY'),
        'api_secret' => env('GODADDY_LINH_API_SECRET'),
        'shopper_id'    => env('GODADDY_LINH_SHOPPER_ID'),
    ],

    'godaddy_vylinh3' => [
        'api_key'    => env('GODADDY_VYLINH3_API_KEY'),
        'api_secret' => env('GODADDY_VYLINH3_API_SECRET'),
        'shopper_id'    => env('GODADDY_VYLINH3_SHOPPER_ID'),
    ],

    'godaddy_vylinh4' => [
        'api_key'    => env('GODADDY_VYLINH4_API_KEY'),
        'api_secret' => env('GODADDY_VYLINH4_API_SECRET'),
        'shopper_id'    => env('GODADDY_VYLINH4_SHOPPER_ID'),
    ],

];
