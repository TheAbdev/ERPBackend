<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://erp.assar.sa',
        'http://erp.assar.sa',
        'https://backerp.assar.sa',
        'http://backerp.assar.sa',
        'http://localhost:4200',
        'http://localhost:3000',
        'http://localhost:8000',
        'http://localhost:5173',
    ],

    'allowed_origins_patterns' => [
        '/^https?:\/\/[a-z0-9-]+\.(ngrok-free\.app|ngrok-free\.dev|ngrok\.io|ngrok\.app)(:\d+)?$/',
        '/^https?:\/\/localhost(:\d+)?$/',
        '/.*ngrok.*/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'ngrok-skip-browser-warning',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];

