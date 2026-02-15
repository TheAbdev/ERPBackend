<?php

return [
    'base_url' => env('ZKBIOTIME_BASE_URL', 'https://zkbiotime.xmzkteco.com'),
    'username' => env('ZKBIOTIME_USERNAME'),
    'password' => env('ZKBIOTIME_PASSWORD'),
    'token' => env('ZKBIOTIME_TOKEN'),
    'auth_type' => env('ZKBIOTIME_AUTH_TYPE', 'Token'),
    'verify_ssl' => env('ZKBIOTIME_VERIFY_SSL', true),
    'timeout' => env('ZKBIOTIME_TIMEOUT', 30),
    'timezone' => env('ZKBIOTIME_TIMEZONE', config('app.timezone')),
    'default_page_size' => env('ZKBIOTIME_PAGE_SIZE', 200),
    'token_cache_minutes' => env('ZKBIOTIME_TOKEN_CACHE_MINUTES', 55),
];
