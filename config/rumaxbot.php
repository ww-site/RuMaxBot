<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | RuMaxBot API base URL
    |--------------------------------------------------------------------------
    |
    | Production: https://rumaxbot.ru
    | Without trailing slash.
    |
    */

    'base_url' => env('RUMAXBOT_BASE_URL', 'https://rumaxbot.ru'),

    /*
    |--------------------------------------------------------------------------
    | API token
    |--------------------------------------------------------------------------
    |
    | Bearer token (mbk_...) from the rumaxbot.ru personal cabinet.
    |
    */

    'token' => env('RUMAXBOT_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('RUMAXBOT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Outgoing webhook secret
    |--------------------------------------------------------------------------
    |
    | Secret from API key settings. Used to verify X-MaxBot-Signature (HMAC-SHA256).
    |
    */

    'webhook_secret' => env('RUMAXBOT_WEBHOOK_SECRET'),

];
